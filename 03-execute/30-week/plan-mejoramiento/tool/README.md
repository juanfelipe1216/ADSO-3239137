# Herramientas de demostración y verificación

Los datos de negocio se crean exclusivamente mediante la API autenticada. Los scripts no ejecutan SQL ni agregan rutas al contrato.

## B14: sembrador y rendimiento

Con Node 24, Docker Desktop y el archivo .env del proyecto configurado, ejecutar desde la raíz:

```sh
node --test tool/clock-scope.test.mjs
node tool/run-demo.mjs seed
node tool/run-demo.mjs performance
```

Cada ejecución crea un proyecto Compose temporal con volúmenes nuevos y un puerto de loopback (8083, configurable mediante DEMO_APP_PORT). No ejecutar dos modos a la vez en el mismo puerto. Las credenciales se leen de .env/entorno y no se imprimen.

El modo seed crea 40 equipos, 10 investigadores y 15 préstamos. Tres préstamos se registran seis días antes mediante el reloj aislado; el reloj vuelve al presente y se renueva el JWT. Diez líneas se devuelven, quedan dos recientes y exactamente tres vencidas. Se verifica el reporte, cantidades y restauración incluso ante un fallo inducido después de autenticar con el reloj pasado.

El modo performance crea 5.000 equipos por HTTP, distribuidos entre las cinco categorías y diez fabricantes sintéticos. Reinicia la API antes de medir ocho consultas en una primera pasada y veinte rondas posteriores. Conserva todas las latencias, p50/p95/máximo y errores. Exige que cada solicitud sea correcta y tarde menos de 300 ms. La primera pasada no purga las cachés de PostgreSQL ni del sistema operativo; no representa una base con buffers fríos.

El reloj de demostración solo se habilita en el override infra/compose.demo.yaml con APP_ENV=testing. El archivo se monta como solo lectura en la API, y el script lo modifica mediante reemplazo atómico en el host. El modo normal/producción rechaza DEMO_CLOCK_FILE. Se restaura en finally; un cierre forzado del proceso o de la máquina requiere revisar el archivo antes de reutilizar la instancia.

Los resultados JSON quedan en tool/results/local/<proyecto>/result.json, ignorados por Git. La evidencia revisada se conserva en docs/evidence. Al terminar, los scripts restauran el reloj y eliminan exclusivamente su proyecto temporal; el catálogo principal no recibe esos datos. Las imágenes pueden permanecer en la caché de Docker. Un error o incumplimiento devuelve código distinto de cero.

## B13: verificación de Compose

Desde la raíz: node tool/verify-compose.mjs. Usa un proyecto temporal, volúmenes nuevos y puerto 8082 (VERIFY_APP_PORT permite otro). Verifica frontend, proxy, login/admin, categorías, 401 y reinicio. Elimina solo sus recursos temporales. Requiere Compose compatible con !reset/!override.

Ninguna herramienta altera fechas de Git ni crea commits programados.
