# B02 / T-08 parcial: catálogo de equipos

Fecha de sesión: 16 de noviembre de 2026. Commit: `git log --all --grep="feat(catalog): manage active equipment with version checks (T-08 partial)"`.

Los seis servicios de alta, edición, baja, detalle, búsqueda y categorías usan puertos propios. Las operaciones de escritura requieren admin antes de acceder al recurso. Los lectores mantienen el total aunque la página esté vacía; búsqueda literal sin distinguir mayúsculas en nombre/fabricante, combinable con categoría; orden por nombre e id. Referencia inmutable en edición y reutilizable después de una baja lógica, sin borrar el registro histórico.

El repositorio entrega un `StoredEquipment` con versión interna. Cada edición/baja exige esa versión al guardar; si otro escritor ganó, se rechaza con `EQUIPMENT_NOT_AVAILABLE` sin sobrescribir su stock. La versión no entra al dominio ni a JSON ni añade columnas. El adaptador PostgreSQL deberá usar `xmin` según ADR-002. Se introdujo el puerto `Clock`, con reloj fijo para probar la baja UTC.

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

Resultado en PHP 8.4.27 / PHPUnit 11.5.58: **141 pruebas, 398 aserciones, todas aprobadas**, dieciocho archivos de pruebas. Diecinueve casos nuevos: roles, duplicados y conflicto de inserción, categorías, stock al editar, referencia/foto conservadas, baja lógica, bloqueo con pendientes, detalle inactivo, filtros, páginas vacías, búsqueda literal y escrituras obsoletas. Los casos de edición/baja intercalan una retirada en el fake antes de guardar y comprueban que esta no se pierde.

Límites: la intercalación en memoria prueba el protocolo de aplicación, **no demuestra concurrencia PostgreSQL ni RNF-2**. T-12/T-13 deben ejecutar sus pruebas reales. El listado de categorías se prueba con fixtures; las cinco categorías oficiales ya se comprueban por migración en SchemaConstraintsTest. Fotografías siguen pendientes de B05/T-15 y T-08 continúa parcial. Las rutas HTTP siguen pendientes de T-16.
