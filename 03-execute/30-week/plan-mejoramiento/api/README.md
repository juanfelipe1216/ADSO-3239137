# API Laravel

Estado del 16 de noviembre: Laravel arranca, la migración inicial está verificada y existen objetos de valor y dominio de equipos, préstamos, investigadores y usuarios (T-01 a T-06). `composer.json`, `composer.lock`, `artisan` y `tests` están directamente en esta carpeta. Las operaciones de negocio por HTTP siguen pendientes.

Responsabilidades: las 21 operaciones de [contrato](../docs/api-contract.md), reglas del laboratorio, seguridad, transacciones y adaptadores. [Arquitectura](../docs/architecture.md) define las dependencias; [tareas](../docs/tasks.md) T-01 a T-19 definen su evidencia.

No conservar migraciones de ejemplo para usuarios con email, caché, colas, sesiones o reset de contraseñas. Las seis tablas del negocio se crean exclusivamente por migración en `lab_equipment`; la contabilidad de migraciones Laravel queda fuera. El admin se aprovisiona con valores de entorno, nunca con un password publicado.

T-07 implementa `LoginService` y `CreateLabTechnicianService` con puertos propios y fakes reutilizables. Se verifica autorización admin, rol de alta limitado a laboratorista, normalización, contraseña de 12-128 caracteres y duplicados, sin Laravel ni base de datos en esas pruebas. Los adaptadores reales y la conexión HTTP siguen pendientes: no hay aún login operativo en la API.

Desde la raíz del proyecto: `docker compose --env-file .env -f infra/compose.yaml up --build -d` y `docker compose --env-file .env -f infra/compose.yaml exec -T api composer test`. El segundo comando ejecuta actualmente 122 casos y 347 aserciones, en dieciséis archivos de pruebas; el mínimo numérico se alcanza, pero el contrato completo y la integración siguen pendientes.

T-08 incorpora el alta de investigadores con documento único, validaciones sin efectos parciales y pruebas con fakes. También están probadas las consultas de investigadores con filtros, paginación y conteo de líneas pendientes. Siguen pendientes el catálogo, las fotografías y los adaptadores reales.

Antes de considerar lista esta carpeta: esquema inspeccionado, al menos 15 archivos de pruebas significativas, contrato completo, concurrencia real y respuestas sin secretos.
