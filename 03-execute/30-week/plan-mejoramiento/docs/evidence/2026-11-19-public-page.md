# B16 · Página pública del producto (T-23)

Fecha real: 19 de noviembre de 2026. T-23 se verifica con una página estática independiente de la API.

## Cambios

`page/index.html` y `page/styles.css` forman un sitio sin JavaScript ni recursos remotos. Explica el propósito del préstamo de laboratorio, el flujo de consulta/préstamo/devolución, las cinco categorías, las reglas (dos equipos, cinco días, bloqueo por vencidos, última unidad), los vencidos y el reporte, y una tabla de funciones por rol. No ofrece reservas, pagos ni acceso, no muestra credenciales ni datos de personas reales (el ejemplo de préstamo está marcado como ilustrativo) y no usa vocabulario de examen ni de herramientas internas.

Accesibilidad básica: `lang="es"`, un solo `h1`, enlace para saltar al contenido, foco visible, tabla con `th scope` y leyenda para lectores de pantalla, colores con contraste legible y respeto de `prefers-reduced-motion`.

## Comprobación

Servida localmente como archivos estáticos (sin API ni base de datos en la ruta) y revisada en el navegador integrado:

- Un `h1`, `lang="es"`, cero imágenes, un único recurso cargado (`styles.css`).
- Escritorio (1280 px): `scrollWidth` igual al ancho visible; sin desbordamiento.
- 375 px: `scrollWidth` de 375 y ningún elemento fuera del ancho visible; la tabla de roles está dentro de un contenedor con desplazamiento propio (`overflow-x`).
- El texto de la página no contiene los términos «examen», «rúbrica», «framework», «React», «Laravel», «API», «token» ni «contraseña».

## Limitaciones

- No se midió el contraste con una herramienta automática; se usaron los colores del producto, de alto contraste sobre fondo claro.
- No se probó en otros navegadores ni con lector de pantalla.
- Los datos del ejemplo son ilustrativos. La página no se publica en ningún servidor; se abre desde el archivo o con cualquier servidor estático.
