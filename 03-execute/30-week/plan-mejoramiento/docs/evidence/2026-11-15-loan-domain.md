# T-05: préstamo y líneas

Fecha real: 15 de noviembre de 2026. Commit: localizar con `git log --all --grep="feat(domain): preserve loan snapshots and return rules (T-05)"`.

Entorno: PHP 8.4.27, PHPUnit 11.5.58, Laravel 12.72.0 y PostgreSQL 16 en Docker Desktop.

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

Resultado obtenido: **59 pruebas, 170 aserciones, todas aprobadas** en nueve archivos. `LoanTest` y `LoanItemTest` añaden 14 casos. Se pasan instantes fijos a los objetos, sin depender del reloj del sistema ni esperar tiempo real. Se verifican:

- Una o dos líneas; equipos e identificadores de línea no repetidos.
- Cinco días UTC, incluido un cambio de horario estacional en la zona de origen.
- Vencimiento estricto: al instante exacto sigue abierto; un segundo después está vencido. Días de retraso completos, incluido cero antes de las 24 horas.
- Devolución parcial, cierre total, segunda devolución rechazada y línea ajena rechazada.
- Nombre, referencia y categoría congelados pese a posteriores cambios de catálogo.
- Objetos inmutables y rechazo de devoluciones anteriores al préstamo.

Límites: el máximo acumulado de dos pendientes por investigador y el bloqueo por vencidos son reglas entre agregados, pendientes de T-09/T-13. Crear una línea no descuenta stock por sí mismo: el servicio debe coordinar préstamo y equipos en una transacción. No se afirma que exista aún préstamo HTTP ni devolución concurrente comprobada. El puerto de reloj y su adaptador pertenecen a la capa de aplicación; el dominio recibe únicamente instantes.
