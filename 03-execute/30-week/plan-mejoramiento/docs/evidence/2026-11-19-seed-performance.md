# B14 · Sembrador HTTP y rendimiento

Ejecutado el 19 de noviembre de 2026. T-21 verificada. RNF-1 cumple el umbral en la muestra y entorno descritos; no representa una garantía de latencia en otras máquinas o bajo otra carga.

## Sembrador

Comandos desde la raíz con .env configurado:
- node --test tool/clock-scope.test.mjs
- node tool/run-demo.mjs seed
- node tool/run-demo.mjs performance

La prueba Node pasó. La suite backend, tras incorporar el reloj, pasó con 331 pruebas y 1.690 aserciones en 42 archivos. El primer avance del reloj se publicó como 05c1619 después de esas pruebas.

La semilla comprobó por API 40 equipos, 10 investigadores, 15 préstamos y exactamente tres líneas vencidas. Diez líneas fueron devueltas y dos permanecen abiertas recientes. El reporte contiene 15 filas y suma 15 solicitudes. Un fallo inducido después de autenticar con reloj pasado comprobó la restauración; después se autentica de nuevo con tiempo real. Todas las altas, préstamos, devoluciones y consultas usan las operaciones HTTP existentes; el archivo de reloj es configuración del entorno aislado, no una vía de escritura del negocio.

Resultado sin secretos: [seed-result.json](2026-11-19-seed-result.json).

## Rendimiento

5.000 equipos creados por HTTP mediante cuatro trabajadores, cinco categorías y diez fabricantes sintéticos. Se reinició la API antes de medir ocho variantes: sin filtros, texto parcial, fabricante, búsqueda vacía, categoría, texto+categoría, última página y página fuera de rango. Primera pasada: ocho consultas. Calentadas: veinte rondas, 160 consultas. Las solicitudes de medición son secuenciales; el tiempo incluye HTTP por Nginx, autenticación, SQL, transferencia y decodificación JSON.

| Medida | Resultado |
|---|---:|
| Solicitudes | 168 |
| p50 | 65,40 ms |
| p95 | 85,20 ms |
| Máximo | 142,81 ms |
| Errores | 0 |
| Solicitudes >= 300 ms | 0 |

Las [168 muestras completas](2026-11-19-performance-samples.json) conservan los valores sin redondear. No se descartaron valores lentos. La primera pasada, etiquetada cold en el artefacto, corresponde a API recién reiniciada: no se purgaron buffers de PostgreSQL ni cachés del sistema operativo. No se simula concurrencia de búsquedas en esta medición.

Entorno: Windows, Node 24.18.0; Docker con 12 CPU lógicas y 12.389.138.432 bytes de memoria; host Intel Core i5-12450HX. PHP 8.4.27/Laravel 12, PostgreSQL 16 y Nginx en imágenes fijadas por digest en infra. Sin cambios al SQL ni al esquema para aprobar esta medición.

## Aislamiento y entrega

Cada modo crea un nombre de proyecto aleatorio, red y volúmenes nuevos. La API de prueba recibe APP_ENV=testing y un directorio de reloj montado en solo lectura. El host reemplaza el archivo atómicamente. Al terminar se restaura system y se eliminan exclusivamente los recursos de ese proyecto; los datos del proyecto principal permanecen intactos. Los resultados locales se ignoran en Git, estos artefactos revisados no contienen usuarios, contraseñas ni tokens.

La restauración mediante finally cubre errores normales e inducidos, no un apagado forzado del equipo. No ejecutar pruebas concurrentes mientras cambia el reloj de la misma instancia.

Commit del segundo avance: git log --grep="feat(tool): verify API seeding and 5000-equipment performance". Estado: 21/23 tareas. B15-B18 siguen pendientes; el siguiente bloque implementa los flujos completos de React.
