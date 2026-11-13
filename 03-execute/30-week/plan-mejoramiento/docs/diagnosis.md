# Diagnóstico A03 y medidas de corrección

Fuentes: acta adjunta y reporte público del curso del 5 de noviembre. Los hallazgos del reporte son resultados de su revisión automática; se distinguen de una nueva inspección del código. No se ha ejecutado de nuevo el examen anterior.

| Informe | Resultado anterior | Hallazgo que guía este trabajo | Corrección y evidencia nueva |
|---|---:|---|---|
| 1. Commits | 0,0 | Nueve commits fuera de la ventana evaluada; el informe marcó historial reescrito en seis repositorios | Ocho fechas reales, mínimo cuatro commits sustantivos por subcarpeta, push diario, sin amend/rebase/force-push |
| 2. Documentación | 0,0 | Dos commits previos; solo dos tipos de documentos originales modificados; 22 archivos sin cambios | Especificación propia completa; enlazar HU/CA/RN/RNF/T/ADR; actualizar durante cada implementación |
| 3. Congruencia | 91,8 | El evaluador no encontró `laravel/framework` en el composer inspeccionado; contó 13 archivos de pruebas; no encontró ciertas variables ni tres vencidos | `api/composer.json` visible con Laravel real; >=15 pruebas significativas; configuración explícita; sembrado verificable |
| 4. Página | 94,8 | Cumplió; faltó presentar el concepto de vencidos | Presentar equipos, categorías, investigadores, roles, préstamos, devolución, vencidos y reporte sin jerga del examen |
| 5. Completitud | 87,4 | API evaluada al 53 %; T-13, T-14, T-17 y T-18 quedaron en cero | Evidencia de concurrencia, JWT/hash, arranque/admin y CHECK; cerrar las 23 tareas, no solo el núcleo |
| 6. Extras | 63,0 | Endpoint de borrado de socios, siete tablas del framework, cuatro campos adicionales y timestamps en usuario | No borrar investigadores; retirar migraciones de ejemplo; seis tablas del negocio sin campos adicionales |
| 7. Idioma | 51,1 | Señaló `Todas`, `disponibles`, `resultados`, `por`, `para` | Revisar los identificadores y comentarios técnicos; conservar únicamente los textos al usuario y documentación en español |

Acumulado del acta: 388,1/700 = 55,4 %, juicio D. El recálculo mencionado en el acta, incluyendo la noche anterior, tampoco aprobó: 66,6 % y mínimo de 35,0.

## Detalles que no deben perderse

- Los documentos anteriores afirmaban Laravel 12, 58 pruebas y algunas comprobaciones manuales. El informe, en cambio, no detectó Laravel en la ruta inspeccionada y contó 13 archivos: número de casos no equivale a número de archivos. Hay que entregar una estructura directa y evidencia que el revisor pueda ejecutar.
- La documentación anterior admitía no medir 5.000 registros, cubrir solo parte del contrato y probar pocos servicios con fakes. Esas limitaciones se convierten ahora en tareas concretas, no en afirmaciones de cumplimiento.
- La eliminación de socios había sido agregada a petición posterior y documentada como extensión. En este nuevo trabajo CA-05.3 se conserva: ningún borrado de investigadores.
- El informe de idioma es una detección automática, no prueba por sí solo que cada palabra señalada fuera un identificador. La revisión nueva distinguirá cadenas visibles de nombres del código.
- El acta identifica el horario del examen como 22 de octubre de 19:00 a 23:00; el repositorio y el informe usaron el 23 de octubre de 07:00 a 11:00. Se conserva esta discrepancia sin inventar una resolución. Para la nueva entrega se usa la fecha explícita del acta: 19 de noviembre, 18:00.

## Comprobación antes de cada commit

1. Cambio con propósito único y relacionado con una HU, T o corrección documentada.
2. Revisión del diff: no secretos, dependencias instaladas, archivos generados ni trabajo ajeno.
3. Prueba adecuada al cambio y resultado registrado; nunca una marca de terminado por simple conteo de archivos.
4. Documentos afectados actualizados junto con la implementación real.
5. Commit con fecha real; push del avance al fork y verificación de su presencia remota.
