# Infraestructura

Estado del 14 de noviembre: Compose construye y ejecuta la API Laravel en PHP 8.4, expuesta solo en loopback en el puerto 8081, con healthcheck. PostgreSQL 16 y React aún están pendientes; T-20 no se considera cumplida. La página pública podrá servirse como recursos estáticos desde el servidor de la app.

La infraestructura crea motor, red y volumen; **no contiene DDL de negocio**. La API ejecuta sus migraciones tras la salud de la base, crea categorías y aprovisiona admin desde entorno de forma idempotente. El proceso debe fallar claramente si faltan secretos, sin imprimirlos.

Comando verificado para la API inicial: `docker compose -f infra/compose.yaml up --build -d`, desde la raíz del proyecto. Pruebas: `docker compose -f infra/compose.yaml exec -T api composer test`. Las dependencias PHP se instalan en la imagen a partir del lockfile; no se versiona vendor.

Configuración: consultar [plan técnico](../docs/plan.md). Probar desde un volumen nuevo aislado, conservar datos existentes y documentar puertos, logs, salud, reinicio y almacenamiento de fotos. El modo de demostración del reloj estará aislado y deshabilitado para uso normal/producción. En la revisión inicial se detectó Docker instalado pero su motor no estaba en ejecución.
