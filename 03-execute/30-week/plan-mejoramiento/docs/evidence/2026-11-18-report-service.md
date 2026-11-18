# B09 / T-11 parcial: servicio de reporte con fakes

Verificado el 18 de noviembre de 2026. HU-10, RN-4, RN-6 y ADR-004. El servicio depende de `ReportReader`, DTO de fila agregada y dominio; no importa Laravel, SQL ni adaptadores.

`MostBorrowedReportService` deniega al laboratorista antes de validar fechas o leer datos. `DateRange` representa días UTC incluidos mediante inicio inclusivo y día posterior al fin exclusivo. El puerto cuenta líneas por equipo, nombre y categoría congelados, incluidas devueltas; ordena cantidad descendente, nombre/categoría ascendentes y usa id para desempates completos. `MostBorrowedRow` limita la salida a los cuatro campos del contrato, sin identidades, referencia, fabricante ni stock.

Reproducción desde la raíz, con `.env` local y Docker:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api vendor/bin/phpunit tests/Application/MostBorrowedReportServiceTest.php
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

Resultado: **13 pruebas nuevas y 31 aserciones**; suite completa **285 pruebas y 1.036 aserciones**, 31 archivos. Se verifican vacío, fechas obligatorias/malformadas/invertidas, rol denegado sin lectura, límites exactos UTC con microsegundos, filtro por fecha de préstamo y no por devolución/vencimiento, conteo por línea, conservación tras devolución, snapshots tras renombrar/recategorizar, agrupación por id, orden y privacidad de campos. Las pruebas nuevas corren con PHPUnit sin arrancar Laravel.

T-11 continúa parcial hasta el lector PostgreSQL y sus contratos compartidos. HTTP y autenticación por token no están demostrados por estas pruebas. No se agregaron endpoints, columnas ni tablas.

Commit: `git log --all --grep="feat(reports): add frozen most-borrowed report service"`.
