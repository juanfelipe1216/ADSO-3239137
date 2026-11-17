# Distribución de bloques del 16 al 18 de noviembre

Plan ajustado el 16 de noviembre de 2026, después de verificar T-07 y el alta de investigadores. El objetivo del usuario sigue siendo terminar el producto el 17 y estudiar el 18/19. Estos bloques organizan las T-01 a T-23 existentes; **no crean tareas adicionales del spec ni sustituyen sus criterios**.

## Cantidad y viabilidad

Quedan organizados **18 bloques: 4 para la sesión adicional del 16, 12 para el 17 y 2 de revisión el 18**. Un bloque se publica cuando sus pruebas pasan; puede necesitar más de un commit coherente si hay incrementos independientes o correcciones. No es una cuota de commits ni una orden para modificar sus fechas. Si la sesión cruza medianoche, Git conservará la fecha real y se actualizará este registro.

Los doce bloques del 17 **no caben en el horario original de 19:00 a 22:00**. Esta distribución es una secuencia de recuperación de alcance, no una promesa de terminar en tres horas. Requiere trabajo adicional durante el día y resolver las pruebas pendientes; cualquier bloque no verificado mantiene el producto incompleto. El 18 se reserva revisión, sin ocultar desarrollo atrasado como si fuera revisión. La entrega oficial del 19 a las 18:00 y el riesgo de las siete fechas posibles frente a las ocho exigidas siguen vigentes.

## Secuencia y evidencia de cierre

| Bloque | Fecha objetivo | Alcance | Prueba para poder subirlo | Estado |
|---|---|---|---|---|
| B01 | 16 | T-08: búsqueda/detalle de investigadores | Filtros, páginas vacías con total, tamaño 1-50, conteo de líneas | Verificado en esta sesión |
| B02 | 16 | T-08: crear/editar/bajar/consultar equipos y categorías | Roles, referencia única/inmutable, stock, filtros y bajas con fakes | Verificado en esta sesión |
| B03 | 16 | T-18: CHECK de total positivo y rol | PostgreSQL rechaza SQL inválido; esquema documentado | Pendiente |
| B04 | 16 | T-14: Argon2id y JWT real | Hash verificado; firma, expiración, algoritmo y rol rechazados si inválidos | Pendiente |
| B05 | 17, primer tramo | T-08/T-15: fotografía y cierre de servicios de catálogo | JPG/PNG por contenido, 2 MiB, clave opaca y limpieza segura | Pendiente |
| B06 | 17, primer tramo | T-12: repositorios y lectores PostgreSQL | Contratos compartidos con fakes, duplicados, filtros, paginación y total | Pendiente |
| B07 | 17, primer tramo | T-09: prestar equipos | Límite acumulado, vencidos, duplicados, stock y rollback con fakes | Pendiente |
| B08 | 17, primer tramo | T-10: devolver y consultar vencidos | Devolución única, stock, fechas UTC y días completos | Pendiente |
| B09 | 17, segundo tramo | T-11: reporte congelado | Renombrar catálogo no altera agrupación histórica; sin identidades | Pendiente |
| B10 | 17, segundo tramo | T-13: préstamos/transacciones y concurrencia | Conexiones reales: última unidad 201/409; devolución 200/409; límite por investigador | Pendiente |
| B11 | 17, segundo tramo | T-16: 21 operaciones HTTP | Matriz de rutas/roles/validación, error uniforme, sin endpoints extra | Pendiente |
| B12 | 17, segundo tramo | T-17/T-19: composición, admin y arquitectura | Arranque idempotente desde base vacía y detectores de dependencia prohibida | Pendiente |
| B13 | 17, tercer tramo | T-20: Compose y base React | Un comando levanta db/api/app; configuración documentada y sin secretos | Pendiente |
| B14 | 17, tercer tramo | T-21: sembrador API y RNF-1 | 40/10/15, tres líneas vencidas, restauración del reloj; búsqueda con 5.000 equipos y tiempos registrados | Pendiente |
| B15 | 17, tercer tramo | T-22: flujos React | Roles, catálogo, fotos, investigadores, préstamos, devolución, vencidos, reporte y errores | Pendiente |
| B16 | 17, tercer tramo | T-23: página pública y cierre del producto | Sin API, escritorio/375 px, reglas/roles/vencidos; recorrido transversal y todas las tareas comprobadas | Pendiente |
| B17 | 18 | Revisión del proyecto formativo, obligatoria aparte | Evidencia de secciones 00-06 y congruencia; corregir brechas reales y señalar lo no resuelto | Pendiente |
| B18 | 18 | Reproducción final y auditoría del plan | Instalación aislada, pruebas, idioma, secretos, documentación e historial por carpeta/fecha | Pendiente |

El orden es técnico. B03 adelanta la verificación de CHECK porque depende de la migración T-02 ya ejecutable, no de disponer de repositorios; no cambia el esquema exigido. B04 depende de T-07 ya verificada. B10 requiere los casos previos y persistencia; no se declara RNF-2 por probar únicamente fakes. El cierre de B11 puede necesitar comprobación posterior a B12: no marcar T-16 completa antes de tener el sistema compuesto y ejecutar HTTP real.

B17 no garantiza una nota ni sustituye la revisión de la instructora. Si el proyecto formativo requiere cambios extensos, se registra el trabajo adicional; su revisión no puede omitirse para aparentar el cierre de lab-equipment.

## Antes de cada commit

Ejecutar la prueba del incremento y la suite afectada; revisar diff y secretos; actualizar documentación; publicar el avance verificado y comprobar su SHA remoto antes de iniciar el siguiente. No usar amend, rebase, force-push, fechas manuales ni commits vacíos. Los mínimos por subcarpeta se auditan con cambios sustantivos, sin repartir archivos artificialmente para aumentar el conteo.

El 19 antes del mediodía se mantiene la comprobación final de entrega acordada. No se cuenta dentro de los 18 bloques solicitados hasta el 18.
