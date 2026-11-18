# Especificación de Préstamo de equipos de laboratorio

Estado al 18 de noviembre: T-01 a T-14 y T-18 verificadas. T-13 comprueba préstamos y devoluciones concurrentes por HTTP con PostgreSQL. T-11 verifica el reporte con fakes y PostgreSQL. Historias completas por HTTP aún pendientes. Esta especificación mantiene el alcance exigido, no certifica por sí misma su cumplimiento. Las decisiones de adaptación se explican en [ADR-005](adr/ADR-005-domain-adaptation.md). El contrato y el esquema se definen únicamente en [api-contract.md](api-contract.md) y [data-model.md](data-model.md).

## Actores y alcance

| Actor | Acceso |
|---|---|
| Administrador (`admin`) | Catálogo, creación de laboratoristas, investigadores, préstamos, devoluciones, vencidos y reporte |
| Laboratorista (`lab_technician`) | Consultar catálogo; crear y consultar investigadores; operar préstamos, devoluciones y vencidos |
| Investigador | Solicita equipos; es un registro del negocio, no una cuenta |
| Visitante | Consulta la página pública estática; no accede a datos de investigadores |

El administrador inicial se aprovisiona desde el entorno, nunca desde una petición que permita conceder ese rol. Se incluyen fotografía opcional del equipo, cinco categorías fijas, préstamos con una o dos líneas, devolución por línea y reporte agregado. Se excluyen pagos, multas, reservas, renovaciones, notificaciones, importación de archivos en la aplicación, sedes, mantenimiento de categorías, borrado de investigadores, inventario por serial individual y atributos adicionales del equipo.

El fabricante es la adaptación del autor del catálogo de referencia. La referencia reemplaza el ISBN; no se traslada la restricción de 13 dígitos de un libro a un equipo.

## Las diez historias

### HU-01: iniciar sesión

- CA-01.1: credenciales correctas entregan JWT válido durante ocho horas.
- CA-01.2: credenciales incorrectas producen 401 con mensaje genérico, sin indicar si existe el usuario.
- CA-01.3: salvo salud e inicio de sesión, todas las rutas requieren token. Token ausente, inválido o vencido: 401; rol insuficiente: 403 antes de consultar el recurso.

### HU-02: crear laboratoristas

- CA-02.1: solo el administrador crea usuarios con `username`, `password` y rol `lab_technician`.
- CA-02.2: el nombre se recorta y convierte a minúsculas; duplicarlo produce 409.
- CA-02.3: solicitar rol `admin` produce 422. Ni contraseña ni hash se incluyen en respuestas o logs.

### HU-03: gestionar equipos

- CA-03.1: el administrador crea nombre, fabricante, referencia, categoría y total de unidades. Inicialmente disponibles = total.
- CA-03.2: referencia normalizada, obligatoria y única entre equipos activos; duplicada: 409. Formato definido en el contrato.
- CA-03.3: total entero >= 1. Modificarlo ajusta los disponibles en la misma diferencia; no puede dejar disponibles negativos (422).
- CA-03.4: fotografía JPG/PNG de hasta 2 MiB, validada por contenido; se almacena una clave opaca o `null`, nunca una ruta ni cadena vacía.
- CA-03.5: baja lógica. Si hay líneas pendientes del equipo: 409. El histórico permanece consultable.
- CA-03.6: la referencia no cambia al editar; la operación equivalente de la referencia tampoco modifica el ISBN.

### HU-04: consultar equipos

- CA-04.1: texto parcial en nombre o fabricante, filtro por categoría, solo activos; orden por nombre y luego id; paginación con tamaño máximo de 50.
- CA-04.2: la respuesta incluye el total de coincidencias, incluso cuando una página no contiene elementos.
- CA-04.3: id inexistente o dado de baja: 404, también al consultar su fotografía.

### HU-05: gestionar investigadores

- CA-05.1: ambos roles crean nombre y documento; documento duplicado: 409.
- CA-05.2: listado y búsqueda por nombre o documento, paginados. El detalle informa cantidad de líneas sin devolver.
- CA-05.3: los investigadores no se eliminan. No se ofrece endpoint ni botón de borrado.

### HU-06: registrar un préstamo

- CA-06.1: corresponde a un investigador, lo registra el usuario autenticado y contiene una o dos líneas.
- CA-06.2: repetir equipo en el mismo préstamo: 422.
- CA-06.3: cada línea descuenta una unidad. Sin disponibilidad: 409 y rollback completo; no queda préstamo parcial.
- CA-06.4: suma de pendientes anteriores y líneas nuevas <= 2; exceso: 422 con el conteo vigente. Debe respetarse también bajo concurrencia del mismo investigador.
- CA-06.5: cualquier línea vencida sin devolver bloquea préstamos nuevos con 409.
- CA-06.6: cada vencimiento equivale al instante UTC del préstamo más cinco días. La línea congela nombre, referencia y nombre de categoría.
- CA-06.7: el préstamo no se edita ni se borra; el cliente no elige operador ni fecha.

### HU-07: devolver una línea

- CA-07.1: asigna `returned_at` y restituye una unidad dentro de una transacción.
- CA-07.2: línea devuelta: 409; dos devoluciones simultáneas producen un solo incremento.
- CA-07.3: disponibles nunca supera total; PostgreSQL también lo impide.
- CA-07.4: con todas las líneas devueltas, el estado calculado es `closed`.

### HU-08: consultar préstamos

- CA-08.1: detalle con líneas y estado calculado `open`, `overdue` o `closed`.
- CA-08.2: listado por fechas, estado e investigador; orden por fecha descendente e id; paginado.
- CA-08.3: no se almacena el estado. `overdue` requiere una línea sin devolver con `due_at < now`; exactamente al vencimiento aún no está vencida.

### HU-09: consultar vencidos

- CA-09.1: ambos roles ven líneas pendientes cuyo vencimiento es anterior al reloj UTC actual, equipo, investigador y días de retraso.
- CA-09.2: orden por vencimiento más antiguo primero, con desempate por id. Los días se calculan como días completos transcurridos; una línea vencida hace menos de 24 horas puede mostrar cero días completos.

### HU-10: equipos más solicitados

- CA-10.1: solo admin; agrupa por `equipment_id`, nombre y categoría congelados; orden por cantidad descendente, nombre y categoría.
- CA-10.2: fechas obligatorias, ambos días UTC incluidos; rango invertido: 422; sin préstamos: `rows: []`.
- CA-10.3: no incluye investigadores ni operadores.
- CA-10.4: cambios posteriores de catálogo no modifican los valores históricos. Un equipo con diferentes nombres o categorías congelados puede aparecer en varias filas.
- CA-10.5: cuenta las líneas registradas, también las ya devueltas; una devolución no resta solicitudes.

## Seis reglas de negocio

| ID | Regla |
|---|---|
| RN-1 | `0 <= available_units <= total_units` y total >= 1 |
| RN-2 | Cinco días de plazo y máximo dos equipos pendientes por investigador |
| RN-3 | Un investigador con vencidos no recibe nuevos equipos |
| RN-4 | Los valores congelados en la línea no siguen al catálogo |
| RN-5 | Préstamos y devoluciones se conservan; equipos con baja lógica |
| RN-6 | Instantes UTC; interfaz en español; identificadores en inglés |

## Cinco requisitos no funcionales

| ID | Criterio | Medición prevista |
|---|---|---|
| RNF-1 | Búsqueda en menos de 300 ms con 5.000 equipos | Sembrado por API; registrar entorno, muestra de consultas, p50/p95/máximo y errores; exigir p95 < 300 ms y reportar cualquier solicitud que supere el umbral |
| RNF-2 | Dos operadores disputan la última unidad: uno 201 y otro 409 | Peticiones paralelas con conexiones/procesos distintos y PostgreSQL real |
| RNF-3 | Contraseñas protegidas en almacenamiento y transporte; no aparecen en logs | Argon2id, JWT verificado, TLS para acceso remoto, revisión de trazas y respuestas |
| RNF-4 | Un solo cuerpo para errores HTTP | Contratos de autorización, validación, negocio y errores inesperados |
| RNF-5 | Arranque con un comando y motor inicialmente vacío | Compose, migraciones de API, admin por entorno y recorrido desde volumen nuevo |

La medición completa de RNF-1 debe conservar el máximo; un p95 satisfactorio por sí solo no demuestra que todas las consultas cumplan el enunciado de 300 ms. No se declarará cumplido si se observa incumplimiento sin resolver.
