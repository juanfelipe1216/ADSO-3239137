# Fuentes y límites de esta revisión

Revisión realizada el 12 de noviembre de 2026. Se leyeron las seis páginas del acta y se revisó su representación visual; se consultaron los documentos de referencia, el informe A03 y los documentos adicionales del examen anterior.

| Fuente | Versión consultada | Uso |
|---|---|---|
| [Acta A03 adjunta](../Plan-Mejoramiento-A03.pdf) | Acta 03-2026, 5 de noviembre | Requisitos específicos de laboratorio, forma de entrega, ocho días y fechas |
| [Spec Manager Library](https://github.com/code-sena/test-manager-library-3239137-docs) | Commit `50b8205bc4b96fe9796e4a3a8fd20fe6df59fd7d` | Constitution, spec, plan, architecture, panorámica, data-model, api-contract, tasks y cuatro ADR |
| [Informe del 5 de noviembre](https://github.com/code-sena/ADSO-3239137/blob/6654e0a965b5b2765ce32aa6b7522bc012773ac0/docs/informes/2026-11-05-prueba-manager-library-7-informes/index.html) | Fuente HTML y objeto de datos del código A03 | Hallazgos individuales y criterios del examen |
| [Documentos del examen anterior](https://github.com/juanfelipe1216/test-manager-library-3239137-docs) | Commit `4e7521ce5e9aa185554a204e616c01f18f5ca0eb` | Adaptación, estado declarado y ADR-005; no se copiaron resultados de pruebas como evidencia nueva |
| [Repositorio del curso](https://github.com/code-sena/ADSO-3239137) | Commit `6654e0a965b5b2765ce32aa6b7522bc012773ac0` | Estructura e informes publicados |
| [Fork de entrega](https://github.com/juanfelipe1216/ADSO-3239137) | Actualizado el 12 de noviembre sin reescritura | Destino confirmado por el usuario |
| [Informe formativo del 19 de octubre](https://github.com/code-sena/ADSO-3239137/blob/6654e0a965b5b2765ce32aa6b7522bc012773ac0/docs/informes/2026-10-19-entrega-00-06/index.html) | Corte histórico de octubre | Orientar revisión pendiente del proyecto del equipo |

Se compararon los dos specs de referencia: contrato, modelo, historias y ADR coinciden; las diferencias se concentran en herramientas Python/.NET. Aquí se redacta una sola especificación Laravel/React para el dominio asignado. Las instrucciones antiguas de entregar seis forks separados no sustituyen la instrucción del acta de usar seis subcarpetas en un fork.

## Decisiones confirmadas por el usuario

- Inicio el 12 de noviembre de 2026; objetivo de producto terminado el 17.
- Sesiones de 19:00 a 22:00.
- Finalmente se acordaron ocho días de commits reales, con revisiones breves el 18 y el 19 para dejar tiempo de estudio.
- El 19 debe trabajarse antes del mediodía; el cronograma asigna 10:00-10:30.
- Usar `03-execute/30-week/plan-mejoramiento/`. Es la última carpeta publicada; su elección fue confirmada por el usuario y no inferida del calendario ISO.

## Pendiente de evidencia

- Ninguna de las 23 tareas del producto se ha probado todavía.
- Las decisiones de referencia/fabricante y el reloj aislado son precisiones de diseño de este proyecto, señaladas en ADR; no texto literal del instructor.
- La revisión del código anterior no se sustituyó por las afirmaciones de su README: se contrastó el informe con sus documentos, sin volver a ejecutar aquel sistema.
- El reporte del proyecto formativo es histórico. Falta comprobar el estado vigente del equipo y resolver las brechas reales para la segunda revisión obligatoria.
- La documentación no certifica una nota ni anticipa que los ocho días estén cumplidos. Solo el historial real y las pruebas futuras podrán demostrarlo.

Referencias técnicas consultadas: [Laravel 12 y soporte](https://laravel.com/docs/12.x/releases) y [semántica de xmin en PostgreSQL 16](https://www.postgresql.org/docs/16/ddl-system-columns.html).
