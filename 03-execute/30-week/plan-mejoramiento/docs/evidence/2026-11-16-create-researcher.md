# T-08 parcial: alta de investigadores

Fecha real: 16 de noviembre de 2026. Commit: localizar con `git log --all --grep="feat(researchers): register researchers with unique documents (T-08 partial)"`.

`CreateResearcherService` normaliza nombre y documento, verifica duplicados y guarda mediante un puerto de aplicación. Reutiliza `IdGenerator` y las validaciones del dominio. `ResearcherRepository` exige que la inserción rechace un documento ocupado incluso después de una consulta sin coincidencias. No se agregan cuentas, roles, contraseñas ni borrado de investigadores.

## Comprobación

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

Imagen reconstruida, PHP 8.4.27, PHPUnit 11.5.58, Laravel 12.72.0 y PostgreSQL 16. Resultado: **109 pruebas, 320 aserciones, todas aprobadas** en catorce archivos de pruebas. Los fakes y TestCase no se incluyen en el conteo de archivos.

Diez casos nuevos y 36 aserciones: creación y recuperación en el fake; espacios recortados; documentos con ceros iniciales conservados y distintos del valor sin ceros; duplicados sin reemplazar el registro; nombre/documento inválidos sin consultas ni escrituras; longitudes máximas sin truncamiento; propagación del conflicto de inserción.

## Correspondencia y límites

Corresponde al caso de uso de CA-05.1 y respeta CA-05.3. Ambos roles podrán invocarlo desde la capa HTTP autenticada; la autorización HTTP y el 409 real se comprobarán en T-16. El servicio no incorpora un parámetro de rol controlado por el cliente.

No demuestra todavía persistencia PostgreSQL ni concurrencia entre conexiones. Consultas, paginación y conteo de pendientes de CA-05.2, catálogo y fotografías siguen pendientes. T-08 permanece parcial y el total de tareas cerradas sigue en **7/23**. Los catorce archivos aún no satisfacen el mínimo de quince.

El bloque se implementó y probó después de publicar T-07; cada commit incluye únicamente su incremento y documentación relacionada.
