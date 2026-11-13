# Plan técnico

Estado inicial: decisiones de construcción, no resultado ejecutado. El orden diario está en [schedule.md](schedule.md) y los criterios verificables en [tasks.md](tasks.md).

| Decisión | Elección | Justificación y verificación |
|---|---|---|
| Backend | PHP 8.4 y Laravel 12 | Stack compatible con lo usado antes; dependencia real en composer, lock versionado y arranque comprobado |
| Frontend | React 19 y Vite | Componentes por flujo, cliente HTTP central y pruebas de interacción |
| Base | PostgreSQL 16 | Equivalencia con referencia, CHECK, índices parciales y concurrencia |
| Persistencia | Migraciones Laravel y adaptadores de consultas | Seis tablas singulares; evitar atributos y migraciones de ejemplo |
| Seguridad | Argon2id y biblioteca JWT mantenida, HS256 | No implementar criptografía propia; token de ocho horas, secretos por entorno |
| Arquitectura | Puertos y adaptadores | Fakes para la aplicación; dependencias comprobadas automáticamente |
| Concurrencia | Testigo `xmin` y bloqueo por investigador | No agrega campo `version`; defiende stock y límite en solicitudes paralelas |
| Tiempo | Puerto Clock, UTC | Reloj fijo en pruebas; demostración aislada para vencidos |
| Fotos | Volumen local; clave opaca | Validación del contenido y eliminación después del commit |
| Tool | Node.js, HTTP | Datos sintéticos reproducibles; sin SQL directo |
| Pruebas | PHPUnit, pruebas React y HTTP contra PostgreSQL | Al menos 15 archivos significativos; plan inicial de 25 o más |

Laravel 12 mantiene soporte de seguridad durante las fechas del plan según sus [notas oficiales](https://laravel.com/docs/12.x/releases). Las versiones exactas y dependencias se fijarán al preparar los lockfiles, sin afirmar que se ha instalado nada todavía.

## Configuración prevista

`APP_ENV`, `APP_DEBUG=false`, `APP_KEY`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `JWT_SECRET`, `ADMIN_USERNAME`, `ADMIN_PASSWORD`, `CORS_ORIGIN`, `COVERS_DIR`. Solo el modo aislado de demostración admite `DEMO_CLOCK_FILE`.

`.env.example` tendrá nombres e instrucciones, sin credenciales válidas. El admin se crea de forma idempotente en el arranque; no se reemplaza su contraseña en cada reinicio. Sesiones y caché no usan base de datos, no hay colas y no se instalan starter kits que agreguen registro o recuperación de contraseña. La base del contenedor nace vacía; las migraciones y el aprovisionamiento ocurren en la API después del healthcheck de PostgreSQL.

## Orden y definición de terminado

1. Especificar negocio y contrato; registrar decisiones antes del código.
2. T-01/T-02: Laravel, salud, esquema y categorías; infraestructura inicial.
3. T-03 a T-11: objetos de dominio y casos de uso probados con fakes.
4. T-12 a T-19: persistencia, seguridad, fotografías, HTTP, composición e invariantes reales.
5. T-20 a T-23: integración, datos por API, React y página; se inician en paralelo temporal con las capas que necesitan, sin declarar terminado antes de sus dependencias.
6. Cerrar las 23 tareas con resultados reproducibles, comprobar los cinco RNF, hacer el recorrido desde base vacía y ensayar la sustentación.

No se acepta una ruta simulada, un test de ejemplo o una lista de tareas marcada como sustituto de evidencia. Cada cierre requiere prueba, comando, resultado, fecha real y commit. No se prometen resultados antes de ejecutarlos.
