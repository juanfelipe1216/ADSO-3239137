# B11 · Login, catálogo e investigadores por HTTP

El 18 de noviembre se conectaron 18 de las 21 operaciones. CatalogHttpTest verifica login y contraseña con hash real, JWT, alta de laboratoristas, categorías, alta/edición/baja de equipos, fotos, investigadores y reporte contra PostgreSQL. Los 16 casos sin token devuelven 401. Los permisos del administrador se verifican antes de validar identificadores o consultar objetos. Las validaciones rechazan campos adicionales y tipos inválidos.

Las pruebas detectaron que el cliente de PHPUnit adjunta un contenedor JSON vacío a GET. Se admiten cuerpos vacíos o contenedores vacíos y se rechazan campos adicionales. Los errores esperados del dominio quedan excluidos del reporte de excepciones.

Se ejecutó la suite tras reconstruir con Docker Compose. Resultado de la prueba focalizada, 18 casos y 186 aserciones. La suite completa pasó con 309 pruebas y 1.294 aserciones. Reproducción desde la raíz con `docker compose --env-file .env -f infra/compose.yaml up --build -d` y `docker compose --env-file .env -f infra/compose.yaml exec -T api composer test`.

T-16 sigue parcial hasta conectar y probar listado, detalle y vencidos de préstamos. Commit identificable mediante `git log --grep="feat(http): expose authenticated catalog and identity routes"`.
