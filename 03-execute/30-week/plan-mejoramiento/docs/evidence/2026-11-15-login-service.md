# T-07 parcial: caso de inicio de sesión

Fecha real: 15 de noviembre de 2026, sesión de la tarde. Un único bloque y un único commit, localizable con `git log --all --grep="feat(auth): add login use case with fake dependencies (T-07 partial)"`.

`LoginService` normaliza el usuario, consulta el repositorio, verifica la contraseña sin transformarla y solicita un token de 28.800 segundos. Usa interfaces propias, sin depender de Laravel, HTTP, SQL ni adaptadores. El resultado contiene únicamente token, tipo Bearer, duración y rol.

Comprobación sobre la imagen reconstruida, desde la raíz del proyecto:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

Entorno: PHP 8.4.27, PHPUnit 11.5.58, Laravel 12.72.0 y PostgreSQL 16. Resultado: **83 pruebas, 217 aserciones, todas aprobadas**, en doce archivos de pruebas. `LoginServiceTest` añade seis casos y 22 aserciones:

- Ambos roles y usuario normalizado, con duración solicitada de ocho horas.
- Contraseña conservada exactamente, incluidos espacios.
- Mismo código `INVALID_CREDENTIALS` y mensaje para usuario desconocido y contraseña errónea; no se emite token.
- Usuario vacío o demasiado largo rechazado antes de consultar el repositorio.
- Representación del resultado sin contraseña ni hash.

Para un usuario desconocido se llama igualmente al puerto de verificación con hash nulo. El contrato exige que el futuro adaptador real haga una verificación de relleno; estas pruebas no demuestran igualdad de tiempos ni protección contra enumeración por tiempo.

Docker falló al arrancar por sockets temporales bloqueados. Se respaldaron mediante renombrado sus directorios temporales y se reinició el proceso, conservando los volúmenes y datos. Después se reconstruyó el contenedor y pasó la suite completa.

**Límites:** los fakes no implementan Argon2id ni JWT. No se verifican todavía firma, expiración real, persistencia de usuarios ni respuesta HTTP 401. Los errores del caso de uso se traducirán a HTTP en T-16. T-07 permanece parcial hasta implementar y probar creación de laboratoristas con autorización, duplicados y validación. No se avanza a ese bloque en esta sesión.
