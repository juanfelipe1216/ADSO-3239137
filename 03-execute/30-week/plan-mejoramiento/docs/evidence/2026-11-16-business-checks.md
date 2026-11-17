# B03 / T-18: CHECK de total y rol

Fecha de sesión: 16 de noviembre de 2026. Commit: `git log --all --grep="feat(database): enforce positive totals and valid roles (T-18)"`.

Nueva migración de API, sin cambiar la migración inicial ya aplicada ni agregar tablas, columnas o DEFAULT: `ck_equipment_total_positive CHECK (total_units >= 1)` y `ck_user_role CHECK (role IN ('admin', 'lab_technician'))`.

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api php artisan migrate --force
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

La migración se ejecutó en PostgreSQL 16. `BusinessChecksTest` consulta `pg_constraint`, comprueba que ambos CHECK están validados y ejecuta inserciones/actualizaciones inválidas contra el motor. Rechaza total cero incluso con disponibles cero (caso que la restricción anterior permitía), rol desconocido, mayúsculas y espacios. Acepta ambos roles oficiales y total uno con cero disponibles. Cada prueba usa transacción y rollback, sin dejar usuarios ni equipos de prueba.

Resultado completo: **152 pruebas, 420 aserciones aprobadas**, diecinueve archivos de pruebas. PHP 8.4.27 / PHPUnit 11.5.58. Once casos nuevos y 22 aserciones; las pruebas previas siguen comprobando seis tablas, columnas exactas y ausencia de DEFAULT.

T-18 se verifica antes de T-12 porque la migración depende de T-02 y se puede comprobar mediante SQL real sin repositorios. Se corrigió esa dependencia de planificación, sin rebajar el criterio de cierre. T-12 sigue pendiente. Total de tareas verificadas: **8/23: T-01 a T-07 y T-18**.
