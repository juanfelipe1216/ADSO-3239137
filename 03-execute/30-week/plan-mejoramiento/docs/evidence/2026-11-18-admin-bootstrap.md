# B12 · Administrador desde entorno

Primer avance del 18 de noviembre: BootstrapAdminService y comando lab:bootstrap-admin, ejecutado por Compose después de migrar. Configurar ADMIN_USERNAME y ADMIN_PASSWORD (12-128 caracteres), además de JWT_SECRET y DB_PASSWORD, en .env ignorado. La contraseña se guarda con Argon2id. Se conserva el id/hash al repetir; un usuario existente con otro rol o contraseña provoca fallo sin modificarlo. Un conflicto de alta concurrente se resuelve releyendo y verificando la cuenta.

El comando no recibe secretos como argumentos ni los imprime. Si falla, Compose no inicia HTTP. La configuración local recibió un administrador y contraseña aleatoria en .env; no se versionaron sus valores.

Reconstrucción y suite: docker compose --env-file .env -f infra/compose.yaml up --build -d; docker compose --env-file .env -f infra/compose.yaml exec -T api composer test. Resultado: 324 pruebas, 1.510 aserciones. BootstrapAdminTest verifica idempotencia, login, colisión con laboratorista, cambio de contraseña y configuración inválida.

Commit identificable con git log --grep="feat(bootstrap): provision initial admin from environment". T-17 sigue pendiente de arranque desde base vacía aislada; T-19 de pruebas arquitectónicas. Las notas anteriores que indicaban admin pendiente corresponden a B11; este avance implementa su aprovisionamiento.
