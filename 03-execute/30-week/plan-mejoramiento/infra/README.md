# Infraestructura

Estado inicial: configuración pendiente. Servicios necesarios: PostgreSQL 16, API Laravel y aplicación React. La página pública puede servirse como recursos estáticos desde el servidor de la app, sin agregar servicios ajenos al alcance.

La infraestructura crea motor, red y volumen; **no contiene DDL de negocio**. La API ejecuta sus migraciones tras la salud de la base, crea categorías y aprovisiona admin desde entorno de forma idempotente. El proceso debe fallar claramente si faltan secretos, sin imprimirlos.

Comando objetivo tras implementar y configurar: `docker compose -f infra/compose.yaml up --build -d`, desde la raíz del proyecto. Hoy no existe ese Compose y no se declara validado.

Configuración: consultar [plan técnico](../docs/plan.md). Probar desde un volumen nuevo aislado, conservar datos existentes y documentar puertos, logs, salud, reinicio y almacenamiento de fotos. El modo de demostración del reloj estará aislado y deshabilitado para uso normal/producción. En la revisión inicial se detectó Docker instalado pero su motor no estaba en ejecución.
