# API Laravel

Estado inicial: implementación pendiente. El punto de entrada del proyecto PHP será esta carpeta, con `composer.json`, `artisan`, `src`, `database/migrations` y `tests` directamente aquí.

Responsabilidades: las 21 operaciones de [contrato](../docs/api-contract.md), reglas del laboratorio, seguridad, transacciones y adaptadores. [Arquitectura](../docs/architecture.md) define las dependencias; [tareas](../docs/tasks.md) T-01 a T-19 definen su evidencia.

No conservar migraciones de ejemplo para usuarios con email, caché, colas, sesiones o reset de contraseñas. Las seis tablas del negocio se crean exclusivamente por migración en `lab_equipment`; la contabilidad de migraciones Laravel queda fuera. El admin se aprovisiona con valores de entorno, nunca con un password publicado.

Antes de considerar lista esta carpeta: dependencia Laravel visible, esquema inspeccionado, al menos 15 archivos de pruebas significativas, contrato completo, concurrencia real y respuestas sin secretos. No hay comandos de ejecución funcionales todavía.
