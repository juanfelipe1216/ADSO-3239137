# T-07: creación de laboratoristas y cierre de casos de autenticación

Fecha real: 16 de noviembre de 2026. Commit: localizar con `git log --all --grep="feat(auth): create lab technicians with authorization (T-07)"`.

Se contrastaron las seis páginas del acta, la constitución propia, la constitución de referencia, el contrato HTTP y el diagnóstico A03. Se conserva el alcance sin alta pública, asignación de admin por petición ni campos nuevos en la base.

## Cambio y reglas

`CreateLabTechnicianService` comprueba primero el rol del actor autenticado: solo admin. El rol solicitado debe ser exactamente `lab_technician`. Normaliza el usuario, valida la contraseña por longitud en caracteres (12-128, sin trim ni truncamiento), comprueba duplicados y guarda un usuario que contiene únicamente el hash recibido del puerto.

Los puertos `UserRepository`, `PasswordHasher` e `IdGenerator` viven en Application y solo utilizan PHP y dominio. El repositorio debe rechazar duplicados también al insertar; el servicio propaga ese conflicto, aunque la consulta previa no lo hubiera detectado. El actor deberá provenir de autenticación HTTP verificada, nunca del cuerpo enviado por el cliente.

## Evidencia reproducible

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

PHP 8.4.27, PHPUnit 11.5.58, Laravel 12.72.0 y PostgreSQL 16. Imagen reconstruida desde archivos locales. Resultado: **99 pruebas, 284 aserciones, todas aprobadas**; trece archivos de pruebas significativas, sin contar fakes ni clase base.

Se añaden 16 casos y 67 aserciones: alta válida, autorización antes de validaciones/consulta, rechazo de roles distintos de laboratorista, límites de contraseña Unicode, usuario inválido, duplicado normalizado, conflicto de inserción y preservación del usuario anterior. La respuesta serializada se comprueba exactamente como id/username/role. Las pruebas de login siguen pasando y comparten los fakes de repositorio y hash.

## Límites

T-07 queda verificada en su alcance de casos de uso con fakes. Los códigos `INVALID_CREDENTIALS`, `FORBIDDEN_ROLE`, `USERNAME_TAKEN` y `VALIDATION_ERROR` se prueban en aplicación; su traducción a 401/403/409/422 se comprobará en T-16. No se declara probado Argon2id, JWT real, tiempo uniforme de login, logs, concurrencia PostgreSQL ni endpoints de usuarios.

La sesión comenzó con un fallo de la terminal restringida antes de ejecutar comandos. Se usó la ejecución autorizada para leer/escribir y ejecutar Docker. No se cambió la fecha de los commits ni se reescribió el historial.
