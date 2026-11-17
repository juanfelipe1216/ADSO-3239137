# B06 / cierre T-12: catálogo y lectores PostgreSQL

Fecha real de comprobación: 17 de noviembre de 2026. Este segundo avance completa el primero de usuarios e investigadores, publicado en `7dba3e3` tras sus pruebas. No incluye HTTP ni persistencia de préstamos.

## Implementación y reglas

- Repositorio de equipos: alta, consulta activa, duplicado por referencia activa, actualización condicionada por versión y baja lógica. La referencia no se actualiza. Una baja conserva la fila y permite reutilizar su referencia en otro equipo.
- Lectores de equipos, categorías e investigadores: filtros parametrizados, búsqueda sin distinguir mayúsculas, caracteres `%`, `_` y `!` literales, orden estable por nombre e id, páginas vacías con total correcto. Total y filas proceden de una misma sentencia SQL.
- Detalle de investigador: cuenta líneas no devueltas, incluidas vencidas, y excluye devueltas; no cuenta cabeceras de préstamo.
- Composición exclusivamente en `LabServiceProvider`. Sin cambios de esquema, dependencias de infraestructura en dominio/aplicación ni credenciales versionadas.

## Reproducción y resultado

Desde la raíz del proyecto, con `.env` local configurado:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
docker compose --env-file .env -f infra/compose.yaml ps
```

Imagen reconstruida desde los archivos del avance. Entorno: PHP 8.4.27, Laravel 12.72.0, PHPUnit 11.5.58 y PostgreSQL 16. Resultado esperado y obtenido: **208 pruebas, 652 aserciones aprobadas**, API y base saludables. Hay 27 archivos de pruebas significativas.

`IdentityRepositoryContractTest`, `EquipmentRepositoryContractTest` y `ResearcherReaderContractTest` comparan los contratos de fakes y adaptadores reales. Se comprueban recuperación, duplicados, conservación de valores, filtros, orden, paginación, stock, portada, baja y no resurrección de equipos retirados. Las entradas con apariencia de SQL se tratan como datos.

La primera ejecución de los nuevos lectores detectó una aserción demasiado estricta: PostgreSQL `jsonb` devuelve las claves de objetos en otro orden. Se corrigió la comparación de objetos, conservando comprobaciones estrictas del orden de ids de la lista y del total. La suite completa posterior pasó; no se cambió el contrato para ocultar una diferencia de valores.

Las pruebas de investigadores usan rollback. Las de equipos confirman escrituras separadas para verificar `xmin` y eliminan exclusivamente los UUID de sus propios datos de prueba, sin truncar tablas. `xmin` es un testigo de transacción, no un contador por sentencia; no se certifican carreras simultáneas con esta prueba secuencial.

## Alcance del cierre

T-12 y B06 quedan verificados. T-13 todavía debe demostrar transacciones y carreras de última unidad, devolución y límite por investigador; T-16 debe demostrar los códigos HTTP. RNF-1 requiere medir 5.000 equipos, no se deduce del tiempo de esta suite. La composición total y su detector arquitectónico siguen pendientes de T-17/T-19.

Commit de este avance: `git log --all --grep="feat(persistence): query catalog and researchers with PostgreSQL"`.
