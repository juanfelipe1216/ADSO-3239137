Este proyecto es la solución del plan de mejoramiento del aprendiz A03, ficha 3239137, dominio lab-equipment

# Préstamo de equipos de laboratorio

Aplicación para administrar equipos, investigadores, préstamos y devoluciones de un laboratorio. El producto tendrá React, una API Laravel y PostgreSQL 16. Los operadores serán administradores y laboratoristas; los investigadores no tendrán acceso al sistema.

## Estado del 12 de noviembre de 2026

Primera versión de la especificación y de la planificación. **El producto todavía no está implementado ni probado.** Las 23 tareas permanecen pendientes. La ruta confirmada por el usuario en su fork es `03-execute/30-week/plan-mejoramiento/`.

El primer avance documental corresponde al 12 de noviembre. Los ocho días de actividad solo quedarán cumplidos al completar y registrar las sesiones reales hasta el 19; los commits de sincronización del material del curso no sustituyen los avances del proyecto.

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

Pendiente de T-01, T-17 y T-20. El comando objetivo, una vez implementado y configuradas las variables locales, será `docker compose -f infra/compose.yaml up --build -d`. Todavía no es un comando ejecutable de esta entrega. Los secretos se documentarán en `.env.example` sin valores reales.
