# B10 · Carreras de préstamo y devolución

El 18 de noviembre se verificaron T-13 y RNF-2 con dos procesos HTTP de PHP, operadores distintos con JWT firmado y conexiones independientes a PostgreSQL 16. Se conectaron las dos operaciones del contrato necesarias para observar sus respuestas. Las demás operaciones pendientes corresponden a B11.

| Peticiones simultáneas | Respuestas observadas | Estado confirmado en PostgreSQL |
|---|---|---|
| Dos investigadores solicitan la última unidad | 201 y 409 con EQUIPMENT_NOT_AVAILABLE | Una cabecera, una línea y cero disponibles |
| Dos operadores devuelven la misma línea | 200 y 409 con ITEM_ALREADY_RETURNED | Una unidad disponible, fecha de devolución conservada y cero líneas pendientes |
| Un investigador con una pendiente solicita otros dos equipos en peticiones separadas | 201 y 422 con RESEARCHER_LIMIT_EXCEEDED | Dos líneas pendientes en total y una de las dos unidades solicitadas sigue disponible |

La prueba crea registros con UUID propios y abre dos servidores en puertos locales asignados por el sistema. En la carrera de stock una tercera conexión retiene la fila de equipo. En los otros dos casos retiene el bloqueo asesor del investigador. La prueba observa dos sesiones de las peticiones bloqueadas en PostgreSQL antes de soltar la barrera. Comprueba los cuerpos HTTP y las filas confirmadas después de ambas respuestas. Al terminar, cierra los servidores y borra sus datos de prueba.

La suite de Docker pasó con **291 pruebas y 1.108 aserciones**. Desde la carpeta del proyecto, configurar DB_PASSWORD y JWT_SECRET en el entorno local y ejecutar:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api vendor/bin/phpunit tests/Integration/ConcurrentLoanHttpTest.php
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

Compose arranca el servidor de desarrollo con dos trabajadores para admitir peticiones solapadas. Las pruebas emiten sus tokens con el adaptador JWT y un secreto efímero. La ruta de login y el administrador de arranque siguen pendientes. La comprobación manual contra el servicio de Compose sin token devolvió 401 y UNAUTHENTICATED.

La [primera parte de B10](2026-11-18-loan-persistence.md) quedó publicada en `df1cf51`. El commit del cierre se puede localizar con `git log --grep="test(loans): verify concurrent lending and returns over HTTP"`.
