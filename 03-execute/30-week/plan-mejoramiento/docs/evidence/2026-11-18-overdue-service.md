# B08 / cierre T-10: consulta de vencidos

Verificación final y publicación del 18 de noviembre de 2026. La devolución se publicó por separado el 17 en `ef8cbe1`, después de sus pruebas; este avance completa la consulta de vencidos. No se atribuye el cierre al día objetivo original.

## Implementación y corrección

`ListOverdueService` obtiene una sola vez el instante de Clock en UTC. El puerto `OverdueReader` selecciona líneas pendientes con `due_at < now`, ordenadas por vencimiento e id; `OverdueLine` expone exclusivamente `loan_id`, `item_id`, `equipment_name`, `researcher_name`, `due_at` y `days_late`. El nombre del equipo procede del snapshot, no del catálogo actual. La misma lectura del reloj sirve para selección y cálculo de días; no se persisten valores derivados.

Las pruebas de límite encontraron un error real en `LoanItem::daysLate`: restar timestamps enteros descartaba microsegundos y podía contar un día antes de completar las 24 horas. Tres casos fallaron antes del cambio. Ahora se calcula la diferencia entre instantes UTC conservando la precisión; pasan los límites inmediatamente anterior, exacto y posterior, incluidos cambio de zona y horario de verano. No se modificó el requisito de días completos ni la comparación estricta de vencimiento.

## Reproducción

Desde la raíz del proyecto, con Docker funcionando y `.env` local configurado:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api vendor/bin/phpunit tests/Unit/LoanItemTest.php tests/Application/ListOverdueServiceTest.php
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

Las pruebas focalizadas pasaron con **22 casos y 60 aserciones**. La verificación final del 18, después de reconstruir la imagen desde archivos, pasó con **272 pruebas y 1.005 aserciones**, en 30 archivos. PHP 8.4.27, PHPUnit 11.5.58, Laravel 12.72.0 y PostgreSQL 16. Docker estaba detenido al retomar; se inició antes de reconstruir y ejecutar la suite. No se modificaron volúmenes ni fechas de Git para reproducir las pruebas.

La cobertura del lector y servicio comprueba vacío exacto `items: []`, exclusión de devueltos y no vencidos, frontera exacta, fracciones de segundo, orden por fecha e id, nombre congelado tras renombrar catálogo, campos exactos sin documento ni operador, lectura única del reloj, ausencia de escrituras al consultar y actualización de resultados al avanzar el reloj. También ejecuta devoluciones con `ReturnItemService`: desaparece cada línea devuelta, las restantes siguen visibles y el histórico se conserva al cerrar el préstamo.

## Alcance y pendientes

B08/T-10 queda verificado con fakes, con los dos avances publicados después de sus pruebas. El servicio de consulta no conoce Laravel, SQL ni adaptadores. No se agregaron tablas, columnas ni operaciones fuera del contrato.

El lector PostgreSQL, las devoluciones simultáneas con conexiones independientes, la composición real, la autorización de ambos roles y las respuestas HTTP continúan pendientes de T-13/T-16/T-17. Un fake no demuestra RNF-2. El siguiente bloque es B09/T-11, reporte agregado con datos congelados; el producto completo aún está pendiente.

Commit: `git log --all --grep="feat(loans): list overdue items with precise UTC days"`.
