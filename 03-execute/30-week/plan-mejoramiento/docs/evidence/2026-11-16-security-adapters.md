# B04 / T-14: hash y tokens reales

Fecha de sesión: 16 de noviembre de 2026. Commit: `git log --all --grep="feat(security): implement Argon2id and verified JWT adapters (T-14)"`.

## Implementación

`ArgonPasswordHasher` usa Argon2id mediante las funciones nativas de PHP, con sal aleatoria, 64 MiB, cuatro iteraciones y un hilo. No recorta contraseñas ni usa bcrypt como alternativa. Para un hash ausente o inválido realiza una verificación de relleno contra un hash aleatorio de la misma configuración; no se afirma igualdad temporal absoluta. Referencias: [password_hash](https://www.php.net/manual/en/function.password-hash.php) y [password_verify](https://www.php.net/manual/en/function.password-verify.php).

`JwtTokens` usa [firebase/php-jwt](https://github.com/googleapis/php-jwt), instalada y fijada en **v7.2.1** en composer.lock. Firma HS256 y verifica con una clave fijada a ese algoritmo; no implementa criptografía propia. Solo emite sub/role/iat/exp, con duración exacta de 28.800 segundos. Rechaza firma incorrecta, algoritmo distinto/none, UUID/rol inválidos, ausencia o tipos incorrectos de claims, emisión futura y expiración exacta. El puerto `TokenVerifier` devuelve únicamente identidad y rol.

El reloj se inyecta por `Clock`. Al usar el reloj configurable de la biblioteca, restaura timestamp y tolerancia global incluso ante excepción; el adaptador no cede ejecución durante esa operación. El despliegue actual usa peticiones PHP síncronas; un futuro runtime con corrutinas necesitaría revisar este mecanismo.

El proveedor es el único punto de composición de adaptadores de producción. `JWT_SECRET` se lee del entorno mediante configuración; debe tener al menos 32 bytes aleatorios y nunca tiene un secreto predeterminado. La configuración vacía permite salud porque el servicio se resuelve de forma diferida, pero impide emitir tokens cuando se intenta resolverlo. Se añadió su paso por Compose y el nombre vacío a .env.example; no se versionó ninguna credencial.

## Verificación ejecutada

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
docker compose --env-file .env -f infra/compose.yaml exec -T api composer validate --no-check-publish
docker compose --env-file .env -f infra/compose.yaml exec -T api composer audit --no-interaction
```

Se reconstruyó la imagen, se recreó API y se repitió la suite completa: **181 pruebas, 517 aserciones aprobadas**, veintidós archivos de pruebas. PHP 8.4.27, PHPUnit 11.5.58, Laravel 12.72.0 y PostgreSQL 16. Salud HTTP devuelve `{"status":"ok"}`. Composer validó el manifiesto/lock, con advertencia informativa por licencia no declarada; la auditoría no reportó avisos de vulnerabilidad conocidos.

Tres archivos nuevos y 29 casos verifican el hash real, salts diferentes, Unicode hasta 128 caracteres sin truncar a 72 bytes, error de contraseña, JWT válido de ambos roles, manipulación de rol/firma, algoritmo, expiración, tipos/claims, secreto débil o ausente y restauración del reloj de la biblioteca. La prueba de composición crea un laboratorista, inicia sesión y valida el token con adaptadores reales de hash/JWT y repositorio fake. Comprueba que JSON no expone contraseña ni hash y que la salida de depuración de los adaptadores oculta sus secretos. Los secretos de prueba se generan durante la ejecución.

## Límites de cierre

T-14 queda verificada para sus adaptadores y composición de seguridad. La integración HTTP, captura de logs del recorrido completo, TLS remoto y administración desde entorno aún corresponden a T-16/T-17 y RNF-3; no se declara RNF-3 totalmente cumplido. El repositorio de usuarios de la prueba es un fake; T-12 sigue pendiente. No se han abierto nuevas rutas públicas ni agregado registro de admin.

Total de tareas verificadas: **9/23 (T-01 a T-07, T-14 y T-18)**. B01 a B04 completados; quedan los catorce bloques B05 a B18 del plan de recuperación.
