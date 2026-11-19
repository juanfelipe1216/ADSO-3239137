# B17 · Revisión del proyecto formativo Bourgelat (lectura)

Fecha real: 19 de noviembre de 2026. Alcance: secciones 00 a 06 del repositorio `code-sena/bourgelat-docs`, rama `main`, estado remoto `b1fd781` (9 de noviembre de 2026, PR #58). La copia local del equipo estaba en `a1ef5a9` (5 de noviembre), dos PR por detrás; se hizo `git fetch` y la revisión se hizo sobre el contenido remoto exportado a una carpeta temporal. No se modificó ni la copia local ni el repositorio del equipo.

Esta es una revisión de coherencia, no una calificación. No sustituye la evaluación de la instructora ni acredita los 70/100 que exige el acta.

## Método

Comandos de solo lectura (`git fetch`, `git archive`, `git grep` y `grep`/`comm`) para: contar marcadores de pendiente por sección, comparar identificadores entre la matriz de trazabilidad y las historias de usuario, y comparar la arquitectura declarada (ADR, mapa de dominio, catálogo de servicios, modelos y diccionario de datos).

## Estado frente al corte de octubre

No encontré los 66 marcadores del informe de octubre con los patrones TODO, TBD, FIXME, «por definir», «pendiente», casillas `[ ]` y comentarios HTML (no conozco el formato exacto que usó el informe del curso, así que puede haber otros). Las coincidencias que quedan con esos patrones son:

| Sección | Coincidencias | Qué son |
|---|---:|---|
| 00 Gobierno | 39 | Casillas de listas de verificación de plantillas (definition of ready/done, privacidad), no contenido sin escribir |
| 02 Dominio | 1 | `metodoPago`: «valores por definir en models.md» |
| 03 Producto | 1 | Encabezado «Jobs-to-be-done» (falso positivo) |
| 04 Requisitos | 15 | Plantilla de historia, README y 54 estados «Pending» de la matriz |
| 05 Arquitectura | 11 | Ejemplos de código y casillas de plantilla |
| 06 Datos | 13 | Valores de método de pago marcados como provisionales |

## Brechas concretas (con evidencia)

| # | Sección / archivo | Hallazgo | Corrección propuesta |
|---|---|---|---|
| 1 | 05 · `decisions/README.md` | El índice marca ADR-002 como «Proposed», pero `ADR-002` dice «Superseded by ADR-003» | Alinear el estado del índice con el archivo |
| 2 | 05 · `ADR-003` y `ADR-001` | ADR-003 sigue «Proposed — pasa a Accepted cuando el equipo apruebe este PR» aunque ya se fusionó (PR #56) y otros documentos lo aplican; ADR-001 sigue «Proposed - pending team review» | Decisión del equipo: aceptar o dejar explícito que sigue en revisión |
| 3 | 00 · `microservices-documentation.md` (líneas ~22-26, 225) | Describe la v1.0 como monolito modular con una base única regida por ADR-002 y dice que solo hay `01-api-gateway` y `02-auth-service`; contradice ADR-003 | Actualizar la nota de contexto para describir los diez servicios de la fase 1 |
| 4 | 05/09 · nombres de servicios | `auth-service` (gobierno y carpeta `09-microservices/services/02-auth-service`) frente a `identity-service` (catálogo, modelos, diccionario); el catálogo lista diez servicios sin `api-gateway`, pero el detalle empieza en «01 — api-gateway» | Unificar nombres y numeración |
| 5 | 06 · `models.md` y `data-dictionary.md` | `notification-service` tiene tabla en `models.md` (sin encabezado de sección propio) pero no aparece en el diccionario de datos, que cubre nueve servicios | Añadir sección y entradas del diccionario |
| 6 | 04 · `traceability-matrix.md` frente a `user-stories.md` | La matriz tiene 60 requisitos funcionales; las historias solo citan 41. Sin historia: RF-08, 09, 12, 18, 19, 31, 36, 38, 45-49, 51, 53, 54, 56, 57, 60. Estados: 54 «Pending», 8 «In progress», 2 «Done» | Crear/asignar historias o marcar esos RF como fuera de la fase 1 |
| 7 | 02/06 · método de pago | El dominio dice «valores por definir», `models.md` fija valores provisionales (§11 «confirmar con la instructora antes de la primera migración») | Confirmar la lista y cerrarla en ambos documentos |
| 8 | 02/06 · Reporting | El mapa de dominio define el contexto Reporting (11 contextos) sin servicio ni modelo de datos; la matriz lo cita seis veces | Declarar su estado (fase posterior) en el catálogo |
| 9 | Proceso · `CONTRIBUTING.md` | Pide abrir PR contra `develop`, que no existe; las fusiones se hacen en `main` | Corregir la guía o crear la rama |

La cadena problema → producto → requisitos → arquitectura → modelo es coherente en los dominios (once contextos, diez servicios en el catálogo, nueve en datos); las brechas 3, 4, 5 y 8 son las que rompen esa cadena.

## Qué no se hizo

- No se aplicó ninguna corrección: los documentos son del equipo, el cambio llega por PR con revisión del Tech Lead y las decisiones 2 y 7 no son mías ni del aprendiz solo.
- No se revisó el contenido de las secciones 07 a 15, ni la calidad redaccional ni los diagramas.
- No se verificó qué documentos están asignados formalmente al aprendiz; por los PR #38-#41 y los commits de septiembre aparecen contribuciones del aprendiz en gobierno, dominio, producto y visión de arquitectura.
