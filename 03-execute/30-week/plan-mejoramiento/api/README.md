# API Laravel

Estado del 14 de noviembre: Laravel arranca y T-01 tiene tres pruebas de contrato aprobadas. `composer.json`, `composer.lock`, `artisan` y `tests` están directamente en esta carpeta; la migración inicial de PostgreSQL está implementada; dominio y operaciones de negocio siguen pendientes.

Responsabilidades: las 21 operaciones de [contrato](../docs/api-contract.md), reglas del laboratorio, seguridad, transacciones y adaptadores. [Arquitectura](../docs/architecture.md) define las dependencias; [tareas](../docs/tasks.md) T-01 a T-19 definen su evidencia.

No conservar migraciones de ejemplo para usuarios con email, caché, colas, sesiones o reset de contraseñas. Las seis tablas del negocio se crean exclusivamente por migración en `lab_equipment`; la contabilidad de migraciones Laravel queda fuera. El admin se aprovisiona con valores de entorno, nunca con un password publicado.

Desde la raíz del proyecto: `docker compose --env-file .env -f infra/compose.yaml up --build -d` y `docker compose --env-file .env -f infra/compose.yaml exec -T api composer test`. El segundo comando ejecuta actualmente once casos de salud/esquema, en dos archivos de pruebas; no equivale a las quince suites/archivos exigidos ni al contrato completo.

Antes de considerar lista esta carpeta: esquema inspeccionado, al menos 15 archivos de pruebas significativas, contrato completo, concurrencia real y respuestas sin secretos.
