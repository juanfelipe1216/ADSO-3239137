# B11 · Contrato HTTP completo y fotografías reales

Fecha de ejecución: 18 de noviembre de 2026, America/Bogota. T-15 y T-16 quedan verificadas; total 17/23 tareas. Primer avance de B11: `645d765`, login/catálogo/investigadores, publicado después de 309 pruebas aprobadas. El segundo avance se identifica con `git log --grep="feat(http): complete loan queries and verify the 21-operation contract"`. Ambos usan fechas reales y se publican tras sus pruebas.

## Resultado y reproducción

Entorno: Docker Desktop, PHP 8.4.27, Laravel 12.72.0, PHPUnit 11.5.58 y PostgreSQL 16. Desde la raíz del proyecto, con los secretos locales configurados en el archivo ignorado:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

Resultado final: **321 pruebas, 1.493 aserciones, 38 archivos**, todas aprobadas; suite en 12,797 segundos en esta ejecución. El tiempo de la suite no acredita RNF-1. La imagen se reconstruyó antes de probar. Las fixtures usan UUID aleatorios y se limpian mediante rollback o eliminación de sus registros; las fotografías y procesos HTTP temporales también se limpian.

## Comportamientos comprobados

- `CatalogHttpTest`: las 19 operaciones privadas rechazan falta de token; admin crea laboratorista, equipo e investigador, rechaza duplicados y roles indebidos. Login usa hash real y normalización de usuario. El reporte conserva nombre congelado y cantidad tras renombrar y devolver un préstamo creado por HTTP. Baja con pendientes devuelve 409; sin pendientes devuelve 204.
- `LoanHttpTest`: alta, detalle, listado, vencidos y devolución con PostgreSQL. Filtros opcionales por extremo de fecha, rango inclusivo hasta el último microsegundo, investigador y estado; orden por fecha descendente/id, paginación y total en página vacía. Exactamente en el vencimiento sigue abierto; un microsegundo después está vencido y aún tiene cero días completos de retraso. Al devolver pasa a cerrado, se restituye stock y baja el conteo pendiente. Se rechazan parámetros inválidos, recursos ausentes y token inválido.
- `HttpBoundaryTest`: inventario exacto de 21 operaciones (HEAD/OPTIONS de transporte no cuentan como negocio), sin rutas extra; token vencido; 413 y 500 uniformes; una excepción con marcador sensible no expone su mensaje ni en respuesta ni en registro. CORS anuncia únicamente el origen configurado, nunca el origen no autorizado ni un comodín.
- `CoverHttpTest`: un proceso PHP independiente recibe PUT multipart por socket HTTP real y usa PostgreSQL. PNG de exactamente 2.097.152 bytes pasa; exceso y archivo con contenido falso reciben 422. Descarga sin token recibe 401; autenticada entrega MIME/contenido; eliminación devuelve 204, deja clave null y descarga posterior 404.
- Se conserva la cobertura previa de dominio, servicios, snapshots, almacenamiento y carreras de dos procesos HTTP/PG de B10.

La consulta de préstamos obtiene total, página y líneas en una sola sentencia SQL parametrizada; la misma instantánea e instante de Clock determinan filtros y representación. No se agregan tablas ni columnas de estado.

## Correcciones encontradas durante la verificación

El inventario detectó dos rutas automáticas de almacenamiento de Laravel ajenas al contrato. Se deshabilitaron mediante `filesystems.php`; las imágenes permanecen detrás de su ruta autenticada. Se corrigió también la prueba de devolución que no enviaba Authorization al usar una llamada HTTP de bajo nivel.

Los errores esperados del dominio no se registran como excepciones internas. Los inesperados registran solo su tipo y un mensaje fijo, sin argumentos, traza, SQL ni mensajes potencialmente sensibles. Esta decisión reduce el detalle diagnóstico del registro.

El middleware CORS, con un único origen, puede anunciar ese origen fijo también ante otro Origin; el navegador rechaza la discrepancia. La prueba verifica que no se autorice el origen ajeno, en lugar de exigir que desaparezca el encabezado.

## Límites y siguiente bloque

El sistema compone los adaptadores reales en LabServiceProvider y las pruebas usan cuentas temporales. **B12/T-17 aún debe implementar y verificar admin desde entorno y arranque idempotente desde una base vacía**; T-19 debe comprobar dependencias arquitectónicas automáticamente. No se afirma que exista ya una cuenta de admin operativa para uso normal.

Siguen pendientes B12-B18: arranque/admin y arquitectura, Compose con React, sembrador y rendimiento, flujos React, página pública, revisión formativa y auditoría final. HTTP se mantiene en loopback; no se acredita TLS remoto ni RNF-1 por estas pruebas. B11 realizado el 18 es desarrollo atrasado frente al objetivo del 17, no revisión. El requisito de ocho fechas no se considera satisfecho por añadir estos commits.
