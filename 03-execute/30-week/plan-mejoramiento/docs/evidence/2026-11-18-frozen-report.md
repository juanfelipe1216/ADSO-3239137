# B09 / cierre T-11: reporte histórico en PostgreSQL

Verificado el 18 de noviembre de 2026. Segundo avance, posterior al servicio y sus fakes publicados en `d0612de`. HU-10, RN-4, RN-5, RN-6, ADR-004 y artículos I-IV/VIII/X-XII de la constitución.

## Implementación

`PostgresReportReader` agrega exclusivamente `loan` y `loan_item`: cuenta líneas por `equipment_id`, `equipment_name`, `category_name`, filtrando por instante de préstamo y parámetros UTC. Incluye devueltas y no consulta el catálogo para reconstruir nombres/categorías. No une investigadores ni operadores ni devuelve sus identificadores. `MostBorrowedRow` expone solo los cuatro campos agregados. La composición del puerto está únicamente en `LabServiceProvider`.

El orden es cantidad descendente y nombre/categoría ascendentes con collation `C`; id resuelve empates completos sin fusionar equipos distintos. No hay tabla resumen, cambios de esquema, endpoints adicionales ni dependencias nuevas.

## Pruebas y reproducción

Desde la raíz, con Docker y `.env` local:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api vendor/bin/phpunit tests/Integration/FrozenReportTest.php
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

La prueba de integración pasó con **2 casos y 22 aserciones**. Después de reconstruir la imagen, la suite completa pasó con **287 casos y 1.058 aserciones**, en 32 archivos. Entorno: PHP 8.4.27, PHPUnit 11.5.58, Laravel 12.72.0 y PostgreSQL 16.

`FrozenReportTest` ejecuta las mismas expectativas contra fake y lector PostgreSQL resuelto desde el proveedor. Comprueba vacío, inicio incluido, microsegundo anterior excluido, último microsegundo del día final incluido, día siguiente excluido, conversión de zona, conteo de líneas y no de cabeceras, inclusión de devueltas, orden de textos numéricos, separación de equipos homónimos y campos exactos sin identidades.

La segunda prueba renombra y recategoriza realmente un equipo en PostgreSQL, registra líneas con snapshots distintos y confirma tres grupos. Después devuelve la línea pendiente y da de baja el equipo; el reporte previo conserva sus nombres/categorías y las tres líneas permanecen. Esto verifica la lectura histórica tras cambios reales del catálogo, sin afirmar que la escritura productiva de préstamos esté lista.

Los datos sintéticos de préstamos se insertan solo en la prueba porque el adaptador productivo de T-13 sigue pendiente. Todos los datos quedan dentro de una transacción con rollback; se comprueba que enero de 2042 esté libre antes de crear fixtures. Si contiene datos previos, la prueba falla y no los borra. Esto no cambia la regla de sembrado de demostración exclusivamente por API.

## Estado

T-11/B09 verificado: servicio, puerto, lector SQL, composición del lector y contratos compartidos. HTTP, autorización por token y recorrido completo de préstamo por API siguen pendientes de T-13/T-16/T-17. No se declara concurrencia real por ejecutar este reporte.

Commit: `git log --all --grep="feat(reports): aggregate frozen loan data in PostgreSQL"`. Se publica después de probar este avance; no se reescribe el primer commit ni se cambian fechas.
