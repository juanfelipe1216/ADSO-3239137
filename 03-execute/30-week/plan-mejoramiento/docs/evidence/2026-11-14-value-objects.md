# T-03: objetos de valor

14 de noviembre de 2026. Se comenzó este bloque después de subir T-02 en `0e2ebe0`.

Se implementaron Reference, Quantity, DocumentNumber y DateRange en PHP, sin Laravel ni persistencia. Los errores propios del dominio contienen código estable y texto en español, sin código HTTP.

Se ejecutó la suite dentro del contenedor: **31 pruebas y 104 aserciones aprobadas**. Las pruebas nuevas comprueban normalización y límites de referencia, cantidades positivas, documentos con ceros iniciales, límites de longitud, fechas de calendario inválidas, rango invertido y fin inclusivo expresado como límite exclusivo del día siguiente en UTC.

Los cuatro archivos de pruebas unitarias extienden directamente PHPUnit TestCase: no arrancan Laravel ni usan la base. Para repetir únicamente este bloque: `docker compose --env-file .env -f infra/compose.yaml exec -T api vendor/bin/phpunit tests/Unit` después de reconstruir la imagen con `up --build -d`.

No se ha implementado todavía la lógica de préstamos ni se considera cerrada T-04/T-05/T-06. El conteo total es de seis archivos con casos de prueba (dos anteriores y cuatro nuevos), por debajo del mínimo final de quince.
