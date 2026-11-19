# Infraestructura

Estado del 14 de noviembre: Compose construye y ejecuta la API Laravel en PHP 8.4, expuesta solo en loopback en el puerto 8081, con healthcheck. PostgreSQL 16 ya arranca con volumen persistente, cinco categorías y seis tablas creadas por la API; React sigue pendiente; T-20 no se considera cumplida. La página pública podrá servirse como recursos estáticos desde el servidor de la app.

La infraestructura crea motor, red y volumen; **no contiene DDL de negocio**. La API ejecuta sus migraciones tras la salud de la base y crea categorías. El aprovisionamiento idempotente del admin desde entorno está pendiente de B12; no lo realiza todavía este arranque. El proceso debe fallar claramente si faltan secretos, sin imprimirlos.

Preparar `.env` en la raíz a partir de `.env.example`, con una contraseña local aleatoria para `DB_PASSWORD`. Comando verificado para API y base de datos: `docker compose --env-file .env -f infra/compose.yaml up --build -d`, desde la raíz del proyecto. Pruebas: `docker compose --env-file .env -f infra/compose.yaml exec -T api composer test`. Las dependencias PHP se instalan en la imagen a partir del lockfile; no se versiona vendor.

B11 añade `CORS_ORIGIN` a Compose y `.env.example`: configurar el origen exacto del futuro frontend, sin ruta. Vacío no habilita CORS entre orígenes. PHP admite cuerpos de 8 MiB y archivos de 3 MiB para que la aplicación valide el máximo funcional de 2 MiB con error 422; el exceso del límite general devuelve 413. Las fotografías se sirven autenticadas y no hay rutas genéricas `/storage`.

Configuración: consultar [plan técnico](../docs/plan.md). Probar desde un volumen nuevo aislado, conservar datos existentes y documentar puertos, logs, salud, reinicio y almacenamiento de fotos. El modo de demostración del reloj estará aislado y deshabilitado para uso normal/producción. En la revisión inicial se detectó Docker instalado pero su motor no estaba en ejecución.
