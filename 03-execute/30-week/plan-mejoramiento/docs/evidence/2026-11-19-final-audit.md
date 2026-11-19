# B18 · Auditoría y reproducción final

Fecha real: 19 de noviembre de 2026, antes de la entrega prevista a las 18:00 (America/Bogota). Esta auditoría describe lo comprobado y lo que sigue pendiente; no declara cumplido ningún requisito sin evidencia.

## Pruebas y reproducción

Todo se ejecutó el 19 de noviembre sobre el árbol publicado en `main` más los cambios de esta auditoría.

| Comprobación | Comando | Resultado |
|---|---|---|
| Suite PHP | `docker compose --env-file .env -f infra/compose.yaml exec -T api composer test` | 331 pruebas, 1.690 aserciones, 42 archivos de prueba |
| Reloj aislado | `node --test tool/clock-scope.test.mjs` | 1 prueba aprobada |
| React | `npm test` y `npm run build` en `app/` | 31 pruebas en 4 archivos; build de producción aprobado (repetido tres veces sin fallos intermitentes) |
| Instalación aislada | `node tool/verify-compose.mjs` | Tres servicios saludables desde volúmenes nuevos, frontend, proxy, login de administrador, categorías, 401 sin token y reinicio; recursos temporales eliminados |
| Recorrido de interfaz real | Entorno Compose aislado `lab-e2e` | Ver [evidencia B15](2026-11-19-react-flows.md) |
| Página pública | Servidor estático local | Ver [evidencia B16](2026-11-19-public-page.md) |

Semilla (40/10/15, tres vencidos) y rendimiento (5.000 equipos, 168 búsquedas, máximo 142,81 ms) son de B14 y no se repitieron: ningún cambio de hoy toca la API ni el sembrador. Ver [evidencia B14](2026-11-19-seed-performance.md).

## Auditoría

- Secretos: `.env` no está versionado; una búsqueda en los archivos versionados no encontró contraseñas, claves privadas ni tokens con los patrones comunes. No se buscaron secretos en el historial completo.
- Archivos generados: `node_modules`, `dist` y `vendor` no están versionados.
- Idioma: los tres comentarios en español que había en el código React nuevo se tradujeron al inglés; la interfaz y la documentación están en español.
- Reproducibilidad: no hay endpoints nuevos (siguen las 21 operaciones) ni cambios de esquema.

## Pendientes que no se ocultan

1. **Ocho fechas reales de actividad.** Hay siete (12, 14, 15, 16, 17, 18 y 19 de noviembre). El 13 no hubo avances y no se usarán commits ficticios.
2. **Mínimo de cuatro commits sustantivos por subcarpeta.** Estado por carpeta (commits que la tocan): docs 37, api 30, infra 12, app 5, tool 3, page 2. El mínimo no se cumple de forma clara en `page` (un commit sustantivo; el otro es la planificación del 12) ni en `tool` (dos sustantivos; el otro es la planificación del 12). En `app` hay cuatro, uno de ellos solo el archivo de dependencias. No se repartirán archivos ni se harán commits vacíos para aumentar el conteo; si se quiere cumplir, hace falta trabajo real adicional en esas carpetas.
3. **Revisión del proyecto formativo.** B17 se hizo en modo lectura: nueve brechas registradas y ninguna corregida; ver [la revisión](2026-11-19-team-project-review.md). La nota mínima de 70/100 depende de la evaluación de la instructora.
4. **Sustentación.** El acta exige ejecutar, explicar y modificar el producto en vivo el 26 de noviembre. Las pruebas automáticas no lo sustituyen.
5. **TLS remoto (T-14).** Sigue pendiente; el uso de HTTP en loopback es una limitación de desarrollo.
6. **Alcance del recorrido de interfaz.** No se recorrió en el navegador un préstamo vencido ni uno con dos equipos; esos casos se verifican por API (B10, B11, B14) y por pruebas de componentes. Las vistas de reporte y laboratoristas no se midieron a 375 px.
7. **Pruebas de accesibilidad.** Solo revisión manual de etiquetas, roles y foco; sin lector de pantalla ni herramienta automática de contraste.
