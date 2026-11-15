# API Laravel

Estado del 15 de noviembre: Laravel arranca, la migración inicial está verificada y existen objetos de valor y dominio de equipos. `composer.json`, `composer.lock`, `artisan` y `tests` están directamente en esta carpeta. Las operaciones de negocio por HTTP siguen pendientes.

Responsabilidades: las 21 operaciones de [contrato](../docs/api-contract.md), reglas del laboratorio, seguridad, transacciones y adaptadores. [Arquitectura](../docs/architecture.md) define las dependencias; [tareas](../docs/tasks.md) T-01 a T-19 definen su evidencia.

No conservar migraciones de ejemplo para usuarios con email, caché, colas, sesiones o reset de contraseñas. Las seis tablas del negocio se crean exclusivamente por migración en `lab_equipment`; la contabilidad de migraciones Laravel queda fuera. El admin se aprovisiona con valores de entorno, nunca con un password publicado.

Desde la raíz del proyecto: `docker compose --env-file .env -f infra/compose.yaml up --build -d` y `docker compose --env-file .env -f infra/compose.yaml exec -T api composer test`. El segundo comando ejecuta actualmente 45 casos y 134 aserciones, en siete archivos de pruebas; no equivale a los quince archivos exigidos ni al contrato completo.

Antes de considerar lista esta carpeta: esquema inspeccionado, al menos 15 archivos de pruebas significativas, contrato completo, concurrencia real y respuestas sin secretos.
