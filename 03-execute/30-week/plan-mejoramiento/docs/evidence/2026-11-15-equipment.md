# T-04: dominio de equipos

Fecha real: 15 de noviembre de 2026. Commit: localizar con `git log --all --grep="feat(domain): enforce equipment stock and retirement (T-04)"`.

PHP 8.4.27, PHPUnit 11.5.58, Laravel 12.72.0 y PostgreSQL 16 en Docker Desktop.

Desde la raíz del proyecto:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

Resultado obtenido: **45 pruebas, 134 aserciones, todas aprobadas**. Siete archivos de pruebas. `EquipmentTest` añade 14 casos de creación, descuentos, reposición, variación de total, baja, metadatos y clave opaca de fotografía. Los objetos son inmutables: una operación rechazada no deja cambios parciales en nombre ni existencias. La referencia se conserva en las ediciones. La baja convierte el instante recibido a UTC.

Durante la ejecución inicial, una copia de directorio al contenedor duplicó la carpeta de pruebas y produjo una declaración de clase repetida. Se retiraron exclusivamente las carpetas duplicadas del contenedor y se corrigió la copia para incluir su contenido. La suite posterior pasó; no se modificó el repositorio para ocultar ese error.

Límites: son reglas del dominio, sin HTTP ni persistencia de equipos. La unicidad de referencia, el guardado transaccional y las carreras entre operadores se comprobarán en T-08/T-12/T-13. `cover_key` se valida aquí; contenido y tamaño de imágenes siguen pendientes de T-15.
