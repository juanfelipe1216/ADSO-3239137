# T-06: usuarios e investigadores

Fecha real: 15 de noviembre de 2026. Commit: localizar con `git log --all --grep="feat(domain): validate users and researchers (T-06)"`.

Entorno: PHP 8.4.27, PHPUnit 11.5.58, Laravel 12.72.0 y PostgreSQL 16 en Docker Desktop.

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

Se reconstruyó la imagen desde los archivos del proyecto, se recreó el contenedor API y se repitió la suite completa: **77 pruebas, 195 aserciones, todas aprobadas**, en once archivos. La salud por HTTP devolvió `{"status":"ok"}`. La base existente conserva sus datos; esta comprobación no es todavía la prueba de instalación integral sobre volumen vacío de T-17/T-20.

`UserTest` y `ResearcherTest` añaden 18 casos: usuario recortado y en minúsculas Unicode, límites de longitud, roles `admin`/`lab_technician`, rechazo de roles desconocidos, hash privado ausente de JSON y depuración, nombre del investigador y conservación de ceros iniciales del documento. El investigador contiene únicamente identidad, nombre y documento; no recibe cuenta ni credenciales.

El dominio puede representar un administrador para su aprovisionamiento desde entorno. El rechazo de altas de admin por HTTP pertenece a T-07/T-16 y sigue pendiente. También faltan Argon2id real, JWT, unicidad persistida, revisión de logs y operaciones HTTP. Los valores de hash utilizados en las pruebas son marcadores de prueba, no contraseñas ni evidencia de cifrado.

## Continuidad

T-01 a T-06 quedan verificadas. El siguiente bloque es T-07: puertos y casos de uso de login/creación de laboratoristas con fakes; después T-08 conecta los agregados con catálogo e investigadores. Se conservará la entrega por bloques probados y commits con fecha real, sin agrupar al final todos los pushes ni alterar el historial.
