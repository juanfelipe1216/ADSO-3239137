Este proyecto es la solución del plan de mejoramiento del aprendiz A03, ficha 3239137, dominio lab-equipment

# Préstamo de equipos de laboratorio

Aplicación para administrar equipos, investigadores, préstamos y devoluciones de un laboratorio. El producto tendrá React, una API Laravel y PostgreSQL 16. Los operadores serán administradores y laboratoristas; los investigadores no tendrán acceso al sistema.

## Estado del 19 de noviembre de 2026

Laravel y PostgreSQL arrancan en contenedores. Se verificaron 22 de las 23 tareas (T-01 a T-22). Las 21 operaciones están conectadas y probadas: login, usuarios, catálogo/fotos, investigadores, préstamos, devolución, vencidos y reporte. La suite pasa con 331 casos y 1.690 aserciones en 42 archivos, incluidas carreras HTTP contra PostgreSQL y carga multipart real. B12 aprovisiona el administrador desde entorno y verifica el arranque repetido en una base vacía. La base React y el arranque conjunto están verificados en B13. B14 verifica el sembrador por API y 168 búsquedas sobre 5.000 equipos (máximo 142,81 ms). B15 verifica los flujos completos de React por rol (31 pruebas y un recorrido real en un entorno aislado; véase la [evidencia B15](docs/evidence/2026-11-19-react-flows.md)). La página pública sigue pendiente. Véase también la [evidencia B11](docs/evidence/2026-11-18-http-contract.md).

El primer avance documental corresponde al 12 de noviembre. El usuario confirmó que el 13 no hubo avances: entre el 12 y el 19 quedan como máximo siete fechas reales de actividad. El requisito de ocho días queda pendiente de aclaración con la instructora; no se alterarán fechas ni se usarán commits de sincronización del curso para aparentar avances.

## Lectura

1. [Diagnóstico de A03 y correcciones](docs/diagnosis.md).
2. [Cronograma y política de commits](docs/schedule.md).
3. [Constitución](docs/constitution.md) y [especificación](docs/spec.md).
4. [Plan técnico](docs/plan.md) y [arquitectura](docs/architecture.md).
5. [Modelo de datos](docs/data-model.md) y [contrato HTTP](docs/api-contract.md).
6. [Tareas y evidencias](docs/tasks.md), [estrategia de pruebas](docs/testing.md) y [decisiones](docs/adr/README.md).
7. [Fuentes y decisiones pendientes](docs/sources.md), [segunda revisión](docs/team-project-review.md) y [sustentación](docs/defense.md).

## Organización

| Carpeta | Responsabilidad |
|---|---|
| `docs` | Especificación propia, ADR, trazabilidad y evidencias |
| `api` | Laravel: dominio, aplicación, adaptadores y pruebas |
| `app` | React: operación de administrador y laboratorista |
| `page` | Sitio público estático de presentación del producto |
| `infra` | Contenedores, red y volumen de PostgreSQL inicialmente vacío |
| `tool` | Sembrador por HTTP y verificaciones reproducibles |

Se adjunta el [acta con código A03](Plan-Mejoramiento-A03.pdf). Se revisaron visualmente sus seis páginas: el aprendiz figura mediante código; conserva los datos y firmas de la instructora del documento original.

## Hitos

- Producto terminado y verificable: objetivo interno del **17 de noviembre de 2026**.
- Revisión y entrega: **18 y 19 de noviembre**. Fecha límite oficial: **19 de noviembre, 18:00, America/Bogota**.
- Sustentación: **26 de noviembre, desde las 18:00**.
- Si se requiere segunda oportunidad, el acta prevé otro plan para el **3 de diciembre, 18:00**.

La aprobación exige promedio de al menos 70 % en los siete informes, ninguno menor de 50, y al menos 70/100 en la revisión del proyecto formativo del equipo. También exige poder ejecutar, explicar y modificar el producto en vivo.

## Arranque

Desde esta carpeta, con Docker Desktop funcionando, copiar `.env.example` a `.env` y asignar una contraseña local aleatoria a `DB_PASSWORD` y un secreto aleatorio independiente de al menos 32 bytes a `JWT_SECRET`. Configurar también `ADMIN_USERNAME` y `ADMIN_PASSWORD` (12-128 caracteres). No subir `.env`. Después:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d --wait
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

La salud responde en `http://127.0.0.1:8081/api/v1/health`. Este arranque incluye PostgreSQL, API con migración/admin y frontend React en http://127.0.0.1:8080. La contraseña local de la base se recibe de `.env`. `CORS_ORIGIN` admite un único origen de frontend; vacío no habilita acceso desde otro origen. No publicar secretos ni usar el servidor HTTP local como despliegue remoto.
