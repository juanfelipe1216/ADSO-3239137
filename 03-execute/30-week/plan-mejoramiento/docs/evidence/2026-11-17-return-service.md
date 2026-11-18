# B08 / T-10 parcial: devolución por línea

Comprobado el 17 de noviembre de 2026. Se implementa HU-07 con fakes, respetando RN-1, RN-5, RN-6 y la separación entre dominio, aplicación y adaptadores. No se incorporan tablas, columnas ni rutas adicionales.

`ReturnItemService` identifica el investigador del préstamo, abre la transacción con el mismo bloqueo que usa el alta y relee el préstamo. Valida pertenencia y devolución única antes de modificar stock. Clock aporta la fecha; `LoanRepository::markReturned` registra únicamente la devolución de la línea y `EquipmentRepository::save` restituye una unidad condicionada por versión. Si falla una escritura, se revierte la devolución. Se conserva el histórico y los datos congelados.

## Evidencia reproducible

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api vendor/bin/phpunit tests/Application/ReturnItemServiceTest.php
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

La prueba focalizada pasó con **17 casos y 93 aserciones**. Después de reconstruir la imagen, la suite completa pasó con **256 pruebas y 965 aserciones**, en 29 archivos. PHP 8.4.27, PHPUnit 11.5.58 y Laravel 12.72.0; las nuevas pruebas usan únicamente PHPUnit y fakes.

Se verifican: devolución parcial, cierre al devolver todas las líneas, repetición sin segundo incremento ni nueva fecha, préstamo/línea inexistente o ajena, catálogo renombrado, conservación de snapshots, stock lleno o equipo ausente/retirado inconsistente, fecha anterior al préstamo, una lectura de Clock dentro del bloqueo, relectura posterior al bloqueo, conflicto al registrar la devolución, conflicto de versión de stock y fallos `RuntimeException`/`Error` después de escribir la fecha. Se comprueba rollback, conservación del histórico, liberación del bloqueo del fake y reintento posterior. También se ejecuta devolución de vencidos seguida de nuevo préstamo: mientras queda una línea vencida se rechaza; al devolver todas vuelve a permitirse.

T-10 sigue parcial hasta verificar la consulta de vencidos. Esta evidencia no certifica carreras simultáneas ni HTTP: la doble devolución 200/409 en PostgreSQL real corresponde a T-13/B10 y T-16/B11. El fake de bloqueo no simula aislamiento entre procesos. Autenticación, autorización de ambos roles y sintaxis UUID se probarán en HTTP; no hay composición con fakes en producción.

Commit: `git log --all --grep="feat(loans): return individual items atomically with fakes"`. Publicación posterior a pruebas, con fecha real y sin reescritura.
