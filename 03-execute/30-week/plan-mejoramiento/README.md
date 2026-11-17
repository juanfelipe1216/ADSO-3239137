Este proyecto es la solución del plan de mejoramiento del aprendiz A03, ficha 3239137, dominio lab-equipment

# Préstamo de equipos de laboratorio

Aplicación para administrar equipos, investigadores, préstamos y devoluciones de un laboratorio. El producto tendrá React, una API Laravel y PostgreSQL 16. Los operadores serán administradores y laboratoristas; los investigadores no tendrán acceso al sistema.

## Estado del 17 de noviembre de 2026

La base Laravel y PostgreSQL arrancan en contenedores. **El producto de negocio aún está pendiente:** no hay login, catálogo ni préstamos operativos por HTTP. Se verificaron 10 de las 23 tareas: T-01 a T-08, T-14 y T-18. Los servicios de catálogo y fotografías están probados con fakes y el almacenamiento local con disco real; persistencia de negocio y HTTP siguen pendientes. La ruta confirmada por el usuario es `03-execute/30-week/plan-mejoramiento/`.

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

Desde esta carpeta, con Docker Desktop funcionando, copiar `.env.example` a `.env` y asignar una contraseña local aleatoria a `DB_PASSWORD`. No subir `.env`. Después:

```sh
docker compose --env-file .env -f infra/compose.yaml up --build -d
docker compose --env-file .env -f infra/compose.yaml exec -T api composer test
```

La salud responde en `http://127.0.0.1:8081/api/v1/health`. Este arranque incluye API y PostgreSQL con migración automática; frontend y admin siguen pendientes de T-17/T-20. La contraseña local de la base se recibe de `.env`.
