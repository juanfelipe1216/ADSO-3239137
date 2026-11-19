# B12 · Base vacía y arquitectura

El 18 de noviembre se completaron T-17 y T-19. Primer avance publicado: 5db576b. Segundo avance identificable con git log --grep="test(bootstrap): verify clean startup and architecture boundaries".

CleanBootstrapTest crea una base PostgreSQL temporal con nombre aleatorio, ejecuta migraciones y lab:bootstrap-admin en procesos independientes dos veces, verifica una sola cuenta con el mismo id/hash y cinco categorías. Inicia un servidor HTTP independiente conectado a esa base y obtiene un JWT con rol admin mediante login real. Finalmente detiene el servidor y elimina exclusivamente la base temporal. No borra el volumen ni la base de desarrollo. Requiere permiso CREATE DATABASE, disponible en el usuario de desarrollo de Compose.

DependencyRulesTest verifica imports/referencias calificadas de Domain y Application, helpers del framework e instancias de adaptadores fuera de LabServiceProvider. Sus controles se prueban con infracciones deliberadas, incluidas referencias completas y alias. No pretende detectar todas las construcciones dinámicas posibles; complementa la revisión de código. Durante el desarrollo se corrigió un falso positivo que confundía la declaración del namespace Domain con una dependencia externa.

Reproducción desde la raíz:
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test

Resultado final: 328 pruebas, 1.682 aserciones en 41 archivos, todas aprobadas. PHP 8.4.27, PHPUnit 11.5.58, PostgreSQL 16. Duración observada 16,738 segundos; no acredita rendimiento RNF-1.

Configurar ADMIN_USERNAME y ADMIN_PASSWORD junto con JWT_SECRET y DB_PASSWORD en .env ignorado. La cuenta local quedó aprovisionada; consultar sus credenciales únicamente en ese archivo. Cambiar la contraseña en el entorno no modifica una cuenta existente: la discrepancia detiene el arranque para evitar un cambio silencioso. No se agregó ruta HTTP para crear administradores.

Estado: 19/23 tareas verificadas. Siguen pendientes T-20 a T-23, RNF-1, revisión formativa y auditoría de entrega. Próximo bloque B13: Compose con React.
