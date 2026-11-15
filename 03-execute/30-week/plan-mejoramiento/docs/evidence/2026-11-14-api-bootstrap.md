# T-01: arranque Laravel y contrato de salud

Fecha real: 14 de noviembre de 2026, America/Bogota. Este bloque se entrega antes de iniciar las migraciones. Commit asociado: el que incorpora este documento y el esqueleto de API/Compose; se consulta mediante `git log --follow -- docs/evidence/2026-11-14-api-bootstrap.md` desde la carpeta del proyecto.

## Comprobaciones ejecutadas

- `docker compose -f infra/compose.yaml config --quiet`: configuración válida.
- `docker compose -f infra/compose.yaml up --build -d`: imagen construida y API iniciada.
- `docker compose -f infra/compose.yaml exec -T api composer test`: **3 pruebas, 6 aserciones, todas aprobadas**, PHPUnit 11.5.58, PHP 8.4.27.
- Petición HTTP real desde Windows a `http://127.0.0.1:8081/api/v1/health`: respuesta `{"status":"ok"}`.
- Las pruebas comprueban salud pública, ausencia del alias `/up` y rechazo de POST sobre salud con 405 y cuerpo de error.

Se declara `laravel/framework` directamente en `api/composer.json` y se versiona el lockfile generado por Composer. No se crearon migraciones de ejemplo, tablas, usuarios ni credenciales.

## Incidencia de entorno resuelta

Docker Desktop no arrancaba por sockets locales inaccesibles en `Docker/run/dockerInference` y `docker-secrets-engine/engine.sock`. Se conservaron las carpetas temporales con sufijo `a03-backup-*`, sin eliminar imágenes o volúmenes, y se reinició Docker con ambos directorios de sockets limpios. El motor respondió con versión 29.2.1 y permitió construir el contenedor.

## Límites

Solo T-01 está verificada. El error handler inicial cubre salud, 404/405 y fallo genérico; los errores de validación, autenticación y negocio se completarán con sus adaptadores. No se presenta esto como cumplimiento de RNF-4 ni T-16. Base de datos, dominio, frontend y sembrador todavía no se han implementado.
