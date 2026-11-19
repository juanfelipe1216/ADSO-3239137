# B15 · Flujos completos de React (T-22)

Fecha real: 19 de noviembre de 2026. T-22 se verifica con pruebas de componentes y un recorrido real contra la API y PostgreSQL en un entorno Compose aislado.

## Cambios

- `app/src/Workspace.jsx` muestra la sesión y una navegación filtrada por rol. Los controles de administrador no aparecen para el laboratorista; la autorización definitiva sigue en la API.
- Vistas en `app/src/views/`: catálogo (búsqueda, categoría, paginación, detalle, crear/editar/baja y fotografía solo admin), investigadores (búsqueda, pendientes, alta), préstamos (historial con filtros, alta con máximo dos equipos y devolución por línea), vencidos (con devolución), reporte (solo admin) y laboratoristas (solo admin).
- Los selectores de investigador y equipo buscan en el servidor (`q`, tamaño 8); nunca cargan todo el catálogo, así que funcionan más allá de 50 registros.
- Las fotografías se descargan con el token (`GET /equipment/{id}/cover` como Blob) y se muestran con URL de objeto que se revoca al desmontar. No se construye ninguna URL pública con `cover_key`.
- Cada vista tiene estados de carga, vacío, error (mensaje e incidencias de la API) y éxito.
- Corrección encontrada al probar: tras una devolución rechazada (409), la recarga del historial borraba el mensaje de error; ahora el error de la acción tiene su propio estado.

## Evidencia reproducible

Desde `app/`: `npm test` y `npm run build`.

- React: 31 pruebas aprobadas en cuatro archivos (login/cliente HTTP, catálogo y operaciones), repetidas tres veces sin fallos tras la corrección anterior; compilación de producción aprobada.
- Recorrido real en un proyecto Compose aislado (`lab-e2e`, puerto 8085, credenciales generadas para la prueba y volúmenes nuevos, eliminados al terminar): login de administrador; alta de equipo desde la interfaz (la referencia se normalizó a mayúsculas); fotografía cargada y mostrada con descarga autenticada; alta de investigador; préstamo de un equipo con fecha de vencimiento a cinco días; devolución con estado final «Cerrado»; reporte con el equipo devuelto; alta de laboratorista (la contraseña no queda en pantalla); inicio de sesión como laboratorista, que solo ve catálogo, investigadores, préstamos y vencidos, sin «Nuevo equipo» ni edición.
- Ancho de 375 px: sin desbordamiento horizontal en las cuatro vistas del laboratorista (las tablas se desplazan dentro de su contenedor).

## Limitaciones

- La subida de fotografía desde el navegador (selector de archivos) se cubre con pruebas de componentes y con el contrato HTTP de B11; en el recorrido real la imagen se cargó por API y se comprobó su descarga en la interfaz.
- No se recorrió en el navegador un préstamo vencido: ese caso depende del reloj aislado de B14. Las vistas de vencidos y de rechazo (409) se cubren con pruebas de componentes y la lista real vacía se mostró sin errores.
- Un préstamo con dos equipos, el límite de dos pendientes y los bloqueos por vencidos se verifican en la API (B10/B11/B14); la interfaz muestra el mensaje que devuelve el servidor.
- No se revisó con lector de pantalla; se usaron etiquetas, roles y foco visible.
- El historial muestra el nombre del investigador consultando su ficha por identificador, porque el contrato del préstamo no incluye el nombre.

La fecha de los commits es la real del equipo; este avance no cambia ninguna fecha anterior.
