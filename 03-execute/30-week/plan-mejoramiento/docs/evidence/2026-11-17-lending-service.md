# B07 / T-09: registrar préstamos con fakes

Fecha real de comprobación: 17 de noviembre de 2026. Requisitos: HU-06, RN-1 a RN-4 y RN-6; artículos I, II, IV, VIII, XI y XII de la constitución. Se conserva el alcance del acta: máximo dos pendientes por investigador, plazo de cinco días y bloqueo por vencidos. No se añaden tablas, columnas, endpoints ni dependencias.

## Avance probado

`LendEquipmentService` permite a ambos roles prestar una o dos unidades distintas. Valida estructura y duplicados antes de leer; dentro del puerto transaccional comprueba investigador y todos los equipos activos, vencidos, límite acumulado y disponibilidad, en ese orden. Los UUID se comparan sin diferencias de mayúsculas para evitar duplicados equivalentes; su validación sintáctica completa corresponde al adaptador HTTP pendiente.

`LoanRepository` consulta todas las líneas pendientes del investigador e inserta un préstamo con sus líneas. `TransactionManager::forResearcher` exige transacción compartida, serialización por investigador antes del callback, confirmación al terminar y rollback ante cualquier `Throwable`. El fake conserva instantáneas de objetos inmutables para probar recuperación; no sustituye la transacción PostgreSQL pendiente.

La identidad del operador procede del actor autenticado. Clock se consulta una sola vez después de adquirir el bloqueo; el dominio convierte a UTC y calcula cinco días. Se congelan nombre, referencia y categoría. Los descuentos se guardan en orden estable de id; una excepción se propaga y revierte los cambios de este intento. Los préstamos previos se conservan. El error de límite incluye `context['active_loans']` con el conteo vigente; la conversión a `details` y código 422 está pendiente de T-16.

## Comandos y resultados

Desde la raíz del proyecto con Docker y `.env` local configurado:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api vendor/bin/phpunit tests/Application/LendEquipmentServiceTest.php
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
docker compose --env-file .env -f infra/compose.yaml ps
```

La prueba focalizada pasó con **31 casos y 220 aserciones**. Después de reconstruir la imagen, la suite completa pasó con **239 casos y 872 aserciones** en 28 archivos de pruebas. API y PostgreSQL quedaron saludables. Entorno: PHP 8.4.27, PHPUnit 11.5.58, Laravel 12.72.0 y PostgreSQL 16. Las pruebas nuevas extienden PHPUnit directamente, sin arrancar Laravel ni consultar PostgreSQL.

Cobertura comprobada:

- Admin y laboratorista, uno o dos equipos, respuesta con campos del contrato, operador del actor y fecha de Clock.
- Petición vacía, más de dos líneas, valores inválidos y equipos repetidos, incluso UUID con distinta capitalización.
- Investigador inexistente, equipo inexistente o retirado; prioridad de existencia antes de vencidos y stock.
- Vencidos antes del límite; límite antes de disponibilidad; conteo de líneas de una o varias cabeceras.
- Un pendiente más una línea nueva permitido; tercer pendiente rechazado; devoluciones antiguas y pendientes de otra persona no bloquean al investigador actual.
- Exactamente al vencimiento permitido; un segundo después bloqueado; conversión de hora local a UTC.
- Stock agotado, orden estable de escritura y conservación de snapshots tras editar el catálogo.
- Fallo en la segunda escritura después de descontar la primera: rollback de existencias y versiones, preservación del histórico y posibilidad de un intento posterior.
- Fallos `RuntimeException` y `Error` después de insertar el préstamo: rollback de préstamo y ambos descuentos, propagación del mismo error y liberación de la marca de bloqueo.
- Clock y revisión de pendientes se realizan después de adquirir el bloqueo definido por el puerto.

## Límites y siguiente bloque

B07 y T-09 quedan verificados **con fakes**. Esto no declara RNF-2 ni HU-06 completa por HTTP: quedan pendientes los adaptadores PostgreSQL y carreras de T-13/B10, el contrato HTTP de T-16/B11 y la composición real. No se instaló ningún fake en producción. El siguiente bloque B08/T-10 implementará devolución y consulta de vencidos con sus propias pruebas.

Commit: `git log --all --grep="feat(loans): implement atomic lending service with fakes"`. Se publica después de pasar las pruebas, con fecha real y sin reescritura del historial.
