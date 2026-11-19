# Herramientas de demostración y verificación

Estado inicial: scripts pendientes. La semilla del negocio usa exclusivamente la API autenticada; no accede por SQL ni crea endpoints administrativos nuevos.

T-21 exige al menos 40 equipos, 10 investigadores y 15 préstamos, con exactamente tres líneas vencidas sin devolver. Los datos serán sintéticos, identificables como demostración y no se mezclarán con información real. El escenario y el reloj aislado se describen en [ADR-006](../docs/adr/ADR-006-demo-clock.md).

Incrementos previstos: cliente HTTP y lectura de entorno; creación de catálogo/investigadores; préstamos/devoluciones y restauración de reloj; comprobación de resultados; carga de 5.000 equipos; solicitudes concurrentes desde procesos/conexiones independientes. Los scripts deben propagar fallos y códigos de salida, sin imprimir contraseñas ni tokens.

Los resultados reproducibles se resumirán en evidencia, con fecha real y entorno. No se utilizará ningún script para crear commits futuros, alterar sus fechas o convertir un cronograma en actividad aparente.

## Verificación de Compose (B13)

Desde la raíz: node tool/verify-compose.mjs (Node 24). Requiere .env configurado y puerto 8082 libre; VERIFY_APP_PORT permite otro puerto. Construye imágenes, crea un proyecto temporal con volúmenes nuevos, comprueba frontend y API por el proxy, login/admin y categorías, reinicia y repite. Elimina únicamente su proyecto temporal al terminar; las imágenes de prueba pueden quedar en caché. El sembrador de T-21 sigue pendiente.
