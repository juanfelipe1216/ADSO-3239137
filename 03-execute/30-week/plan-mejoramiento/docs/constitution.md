# Constitución de lab-equipment

Versión inicial, 12 de noviembre de 2026. Esta especificación adapta el negocio de Manager Library al acta A03 y traduce sus decisiones a React y Laravel. Su existencia no certifica una implementación.

| Artículo | Regla | Evidencia exigida |
|---|---|---|
| I | `api/src/Domain` solo usa PHP estándar; no conoce Laravel, HTTP, SQL ni adaptadores | Prueba de dependencias y prueba unitaria sin arranque Laravel |
| II | `Application` declara interfaces para sus necesidades externas y solo depende del dominio y PHP estándar | Casos de uso con fakes; firmas sin tipos Laravel/Eloquent |
| III | `api/app/Providers/LabServiceProvider.php` es el único punto de composición de puertos y adaptadores | Prueba que detecte instancias de adaptadores fuera del proveedor |
| IV | Cada servicio realiza un caso de uso; puertos pequeños; fakes sustituibles por adaptadores reales | Revisión de responsabilidades y contratos compartidos de repositorios |
| V | Las migraciones de `api` son dueñas de tablas, índices, CHECK y categorías; `infra` inicia un motor vacío | Ningún DDL del negocio en `infra`; reconstrucción limpia |
| VI | PostgreSQL impone las invariantes expresables con CHECK, UNIQUE y FK | Inserciones inválidas rechazadas y consulta del catálogo del motor |
| VII | El estado de préstamo y los días de retraso se calculan | No existen columnas `status` ni `days_late` |
| VIII | Nombre, referencia y categoría se congelan en cada línea | Renombrar o recategorizar el catálogo no cambia reportes pasados |
| IX | Secretos solo desde entorno; no se versionan ni se registran | Revisión de archivos, respuestas y logs; `.env` ignorado |
| X | La documentación describe la implementación comprobada y señala lo pendiente | Cada regla del modelo tiene estado y evidencia; no se encubre un defecto cambiando el requisito |
| XI | Identificadores, rutas, columnas y comentarios técnicos en inglés; documentación y textos al usuario en español | Revisión de PHP, JS/TS, SQL y CSS, separando mensajes de identificadores |
| XII | Una tarea solo se completa con evidencia reproducible | T-01 a T-23 contienen prueba, resultado y commit real |

Las seis reglas RN y los cinco RNF de [spec.md](spec.md) son obligatorios. No se añaden funciones para mejorar una apariencia de completitud. Una modificación que altere negocio, contrato o modelo debe revisarse contra el acta antes de implementarse.

Las correcciones se registran con nuevos commits pequeños. No se cambian fechas, no se reescribe el historial y no se hacen commits vacíos para aparentar actividad. La fecha de planificación no sustituye la fecha real de Git.
