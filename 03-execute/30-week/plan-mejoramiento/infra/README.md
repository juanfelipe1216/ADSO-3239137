# Infraestructura

Estado del 19 de noviembre: Compose construye db, api y app, espera sus healthchecks y ofrece React en http://127.0.0.1:8080. T-20 está verificada con volúmenes nuevos y reinicio. Los flujos React completos corresponden a T-22.

La infraestructura crea motor, red y volumen; **no contiene DDL de negocio**. La API ejecuta sus migraciones tras la salud de la base y crea categorías. Después de migrar, ejecuta lab:bootstrap-admin con ADMIN_USERNAME y ADMIN_PASSWORD. Un fallo impide iniciar HTTP. El proceso debe fallar claramente si faltan secretos, sin imprimirlos.

Preparar `.env` en la raíz a partir de `.env.example`, con una contraseña local aleatoria para `DB_PASSWORD`. Comando para los tres servicios: `docker compose --env-file .env -f infra/compose.yaml up --build -d --wait`, desde la raíz del proyecto. Pruebas: `docker compose --env-file .env -f infra/compose.yaml exec -T api composer test`. Las dependencias PHP se instalan en la imagen a partir del lockfile; no se versiona vendor.

B11 añade `CORS_ORIGIN` a Compose y `.env.example`: configurar el origen exacto del futuro frontend, sin ruta. Vacío no habilita CORS entre orígenes. PHP admite cuerpos de 8 MiB y archivos de 3 MiB para que la aplicación valide el máximo funcional de 2 MiB con error 422; el exceso del límite general devuelve 413. Las fotografías se sirven autenticadas y no hay rutas genéricas `/storage`.

Configuración: consultar [plan técnico](../docs/plan.md). Probar desde un volumen nuevo aislado, conservar datos existentes y documentar puertos, logs, salud, reinicio y almacenamiento de fotos. El modo de demostración del reloj estará aislado y deshabilitado para uso normal/producción. En la revisión inicial se detectó Docker instalado pero su motor no estaba en ejecución.

El frontend sirve archivos compilados mediante Nginx y reenvía /api/ a api:8000. El navegador utiliza un único origen; no necesita CORS para este despliegue. No hay secretos en el bundle de React. APP_PORT controla el puerto local (8080 predeterminado); DB no expone puerto al host. Las imágenes base quedan fijadas por digest para reproducir las versiones probadas. Para actualizar imágenes se requiere reconstruir y verificar nuevamente.

Prueba aislada: node tool/verify-compose.mjs. Usa infra/compose.verify.yaml, un nombre aleatorio de proyecto, volúmenes nuevos y puerto 8082. No elimina los volúmenes del proyecto principal.
