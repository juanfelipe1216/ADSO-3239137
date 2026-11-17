# B06 / T-12 parcial: usuarios e investigadores

Se implementaron los dos repositorios PostgreSQL mediante consultas parametrizadas. La composición permanece en LabServiceProvider; dominio y aplicación no importan Laravel ni SQL. ON CONFLICT actúa sobre la clave de negocio correspondiente, sin convertir una colisión de UUID u otro error de base en un duplicado de usuario/documento.

`IdentityRepositoryContractTest` ejecuta las mismas expectativas contra fake y PostgreSQL: ausencia, alta, recuperación completa, normalización, conservación de ceros iniciales, duplicado sin reemplazo y entradas con texto SQL tratadas como datos. El conflicto no deja inutilizable la transacción y permite volver a consultar el original.

Comprobación del 17/11/2026: `docker compose --env-file .env -f infra/compose.yaml exec -T api composer test`: **203 pruebas, 585 aserciones aprobadas**. Reproducción desde archivos: ejecutar antes `docker compose --env-file .env -f infra/compose.yaml up --build -d`. Los tests PostgreSQL usan rollback. T-12 sigue parcial: faltan catálogo y lectores. No se declara probado HTTP ni concurrencia de préstamos.

Commit: `git log --all --grep="feat(persistence): store users and researchers in PostgreSQL"`.
