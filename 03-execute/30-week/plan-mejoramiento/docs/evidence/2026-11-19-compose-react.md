# B13 · Compose con PostgreSQL, API y React

Fecha real: 19 de noviembre de 2026. T-20/RNF-5 se verifican con tres servicios, migraciones exclusivamente en API, administrador desde entorno y prueba de arranque en volúmenes nuevos. La interfaz inicial ofrece login y cierre de sesión; T-22 sigue pendiente de los flujos de B15.

## Cambios

Nginx sirve el build de React y reenvía /api/ al contenedor API. El navegador usa el mismo origen, conserva Authorization y recibe los errores JSON del servidor. APP_PORT permite cambiar 8080; todos los puertos del host están en loopback, PostgreSQL no publica puerto. El límite funcional de fotos sigue en la API; el proxy no lo reemplaza por un límite menor ni una página HTML.

Node, Nginx, PHP, Composer y PostgreSQL se fijaron por digest después de detectar una etiqueta PHP remota que cambió durante las reconstrucciones. Actualizarlas exige reconstrucción y pruebas. La regla del curso que ignoraba package-lock.json se corrigió con una excepción local; el lockfile quedó publicado en f88e734 después del avance React 6c5bec9. No se reescribió el historial.

## Evidencia reproducible

Desde la raíz, con .env configurado:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d --wait
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
node tool/verify-compose.mjs
```

Desde app: npm ci, npm test y npm run build.

- React: cinco pruebas aprobadas en dos archivos; build de producción aprobado.
- Backend con PHP 8.4.27: 328 pruebas y 1.682 aserciones aprobadas.
- Los tres servicios principales quedaron saludables.
- La verificación aislada crea su propio nombre de proyecto, red y volúmenes nuevos. Comprueba HTML y JavaScript, health por proxy, login/admin, cinco categorías con token y 401 sin token. Reinicia API/app y repite el recorrido.
- El script limpia exclusivamente sus recursos temporales. Conserva los volúmenes principales. El puerto de prueba es 8082, configurable con VERIFY_APP_PORT. Requiere Node 24 y Compose con soporte !reset/!override.
- La pantalla de ingreso se observó renderizada en el navegador integrado. La revisión exhaustiva móvil/escritorio y todos los recorridos de operación pertenecen a B15/B16.

El primer intento aislado quedó detenido durante la construcción y se canceló antes de crear contenedores; se añadió salida de progreso y se reconstruyó. La repetición completó inicio, login, reinicio y limpieza. No se cuenta ese intento interrumpido como evidencia aprobada.

Commit de integración identificable con git log --grep="feat(infra): run React and API through verified Compose stack". Estado: 20/23 tareas. Faltan B14-B18; la entrega completa aún no está terminada.
