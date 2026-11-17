# Cronograma del 12 al 19 de noviembre de 2026

Zona: **America/Bogota (UTC-5)**. Horario confirmado para desarrollo: **19:00-22:00**. El usuario decidió conservar ocho días reales de commits y usar `03-execute/30-week/plan-mejoramiento/`. Producto completo como objetivo interno: **17 de noviembre**. El 18 y el 19 se reservan revisiones breves para permitir estudio; el 19 debe terminar antes del mediodía.

Los horarios son bloques de trabajo, no fechas que se insertarán en Git. Cada commit ocurre cuando su cambio está revisado. Las ocho fechas solo se cumplen después de trabajar y registrar avances reales en cada una. No hay una automatización creada que vaya a realizar las sesiones futuras.

## Ajuste confirmado el 14 de noviembre

La segunda sesión se retomó el 14. El usuario confirmó que no hubo avance el 13 en otra rama o carpeta. Por tanto, trabajando todos los días restantes hasta el 19 solo se podrán registrar siete fechas distintas (12 y 14-19). El criterio de ocho días del acta queda en riesgo de incumplimiento y requiere aclaración con el instructor; este documento no lo modifica ni lo declara aprobado. Se conservan las fechas reales y el cronograma original debajo como línea base, sin atribuir trabajo al día 13.

La sesión del 14 retoma el primer bloque pendiente: Laravel, salud, contenedores y luego esquema/dominio. Cada bloque se verifica, se registra en un commit y se sube antes de comenzar el siguiente. No se acumulan todos los commits para un único push al final de la sesión.

## Sesiones

| Día | 19:00-19:50 | 20:00-20:50 | 21:00-21:40 | 21:40-22:00 |
|---|---|---|---|---|
| Jue. 12 | Acta, informe A03, actualización del fork | Especificación, modelo, contrato y ADR | Tareas, pruebas y cronograma; revisión documental | Commits de los documentos revisados y push del primer día |
| Vie. 13 | Laravel real, salud y base de contenedores (T-01/T-02) | Dominio y pruebas (T-03 a T-06) | Bases React, cliente HTTP de tool, estructura de página; inventario del proyecto formativo | Comprobar dependencias, actualizar evidencias y push |
| Sáb. 14 | Login, usuarios, JWT y casos de uso con fakes (T-07/T-14) | Catálogo, investigadores y fotografías (T-08/T-12/T-15) | React: autenticación y catálogo; sembrado de catálogo; página adaptable | Pruebas del bloque y documentación de discrepancias |
| Dom. 15 | Préstamos, límite y vencidos (T-09/T-13) | Devoluciones, concurrencia y stock (T-10) | React: investigadores y operación; tool: escenario de préstamos; endurecer Compose | Ejecutar carreras reales, registrar resultados y push |
| Lun. 16 | Reporte congelado y contrato completo (T-11/T-16) | Admin, CHECK, arquitectura y arranque (T-17 a T-20) | Completar app/page/tool; tres vencidos y datos por API (T-21 a T-23) | Corregir brechas, revisar documentación formativa y push |
| Mar. 17 | Verificación de 23 tareas y >=15 archivos de pruebas significativas | Carga con 5.000 equipos, revisión móvil y recorrido desde base nueva | Correcciones finales, congruencia docs/código y ensayo en vivo | Producto completo como meta; evidencia final y push |

Dos descansos de diez minutos por sesión están reflejados en los intervalos. Son 18 horas de calendario hasta el 17; es un plazo ajustado para todo el alcance. Los cierres de tarea dependen de sus pruebas, no de alcanzar la hora. Si una prueba falla se corrige antes de marcar cumplido y se registra el desvío; no se afirma una entrega completa con requisitos pendientes.

| Fecha | Horario | Trabajo concreto y evidencia |
|---|---|---|
| Mié. 18 | 19:00-19:30 | Reproducir instalación con configuración limpia en entorno aislado, revisar instrucciones desde cero y registrar resultados reales, tiempos y fallos; commit de la evidencia o corrección observada; push |
| Jue. 19 | 10:00-10:30 | Comprobar fork remoto, ruta, PDF, README, seis carpetas, historial por día/subcarpeta y evidencia de ambas revisiones; registrar acta de verificación final y commit real; push y comprobar SHA remoto |

El bloque del 19 se fija a las 10:00 para respetar la disponibilidad confirmada antes del mediodía y dejar margen hasta las **18:00**, límite del acta. El 18 y el 19 no se reservan funciones principales pendientes. Si surge un defecto nuevo, la evidencia debe reconocerlo y su corrección tiene prioridad sobre declarar terminado.

## Incrementos previstos por subcarpeta

Se requieren **al menos cuatro commits sustantivos en cada una de las seis subcarpetas**, además de los ocho días distintos del proyecto. Un commit puede incluir documentación vinculada al cambio, pero no se dividirá arbitrariamente para inflar el conteo.

| Carpeta | Incremento 1 | Incremento 2 | Incremento 3 | Incremento 4 y posteriores |
|---|---|---|---|---|
| docs | 12: negocio y diagnóstico | 12: arquitectura/modelo/contrato | 12: tareas, pruebas y cronograma | 13-17: decisiones y resultados reales; 18-19: verificaciones de entrega |
| api | 13: salud y migraciones | 13-14: dominio, autenticación y catálogo | 15: préstamo, devolución y concurrencia | 16-17: reporte, seguridad, contrato, esquema y arquitectura |
| app | 13: estructura y cliente HTTP | 14: login y catálogo | 15: investigadores, prestar y devolver | 16-17: vencidos, reporte, roles y estados de error |
| page | 13: contenido semántico del producto | 14: estructura visual adaptable | 15: contenidos de reglas, roles y vencidos | 16-17: accesibilidad, revisión móvil y recursos |
| infra | 13: servicios y red | 14: configuración de entorno y exposición local | 15: volúmenes, dependencias y aislamiento de demo | 16-17: arranque idempotente y prueba limpia |
| tool | 13: cliente HTTP y configuración | 14: equipos e investigadores | 15-16: préstamos, devoluciones y tres vencidos | 16-17: concurrencia, carga y restauración del reloj |

Mensajes describen lo que cambió, por ejemplo `feat(api): enforce researcher loan limit (T-09)` o `test(tool): verify competing loans return 201 and 409 (RNF-2)`. No se preparan commits vacíos ni se simulan horas. Los incrementos anteriores son trabajo previsto, no historial existente.

## Cierre de cada sesión

Acuerdo actualizado el 16 de noviembre: realizar los commits necesarios por avances completos y comprobados, sin imponer un único commit por sesión. Cada cambio mantiene propósito único, pruebas y documentación; se sube cuando está verificado, sin acumular todos los pushes al final ni dividir trabajo para inflar el conteo. Las fechas son reales y no se espacian artificialmente. Esta aclaración sustituye el límite de un commit acordado el 15; no modifica las exigencias del acta ni el historial.

1. Revisar `git diff` y ejecutar las comprobaciones que correspondan al cambio.
2. Añadir rutas específicas, inspeccionar `git diff --cached --check` y `git diff --cached --stat`.
3. Registrar commit con propósito único; no usar amend, rebase, fechas manuales ni force-push.
4. Hacer push al fork y comparar HEAD con `origin/main` después de fetch.
5. Actualizar tareas solo con resultados reales; nunca convertir una estimación en evidencia.

El merge de actualización del curso no cuenta como implementación del plan; sus commits de terceros tampoco cuentan como trabajo de A03. La revisión del historial se limita a esta carpeta y a los cambios propios. La auditoría final debe mostrar fechas reales en Colombia y revisar fechas de autor y de commit, sin modificarlas.

## Segunda revisión obligatoria

La documentación del proyecto formativo también debe alcanzar 70/100. Se reserva inventario el 13, revisión de brechas el 16 y comprobación el 17. El alcance exacto de las correcciones dependerá de la versión actual del equipo; si supera esos bloques, se explicita el esfuerzo adicional, no se declara aprobada por terminar lab-equipment.
