# Tareas y trazabilidad

**Estado al 19 de noviembre: 21/23 tareas verificadas.** T-01 a T-21 tienen evidencia. T-13 comprueba persistencia y carreras por HTTP en PostgreSQL. T-15 verifica multipart real y T-16 las 21 operaciones, roles y errores; [evidencia B11](evidence/2026-11-18-http-contract.md). T-17 y T-19 verificadas en B12: base vacía, reinicio, login y detectores arquitectónicos.

| ID | Tarea adaptada | Depende de | Requisito / evidencia de cierre | Objetivo | Estado |
|---|---|---|---|---|---|
| T-01 | Laravel, estructura y salud | - | `/api/v1/health` devuelve status ok; contenedor ejecutable | 13 | Verificada el 14 |
| T-02 | Migración de seis tablas y cinco categorías | T-01 | Esquema exacto, UUID, FK, UNIQUE, CHECK de stock, sin DEFAULT | 13 | Verificada el 14 |
| T-03 | Reference, Quantity, DocumentNumber, DateRange | T-01 | Pruebas de formato y normalización; total positivo; fin >= inicio | 13 | Verificada el 14 |
| T-04 | Equipment: retirar, restituir, cambiar total, baja | T-03 | HU-03/HU-04; stock siempre en rango | 13 | Verificada el 15 |
| T-05 | Loan/LoanItem, estados y cinco días | T-04 | HU-06 a HU-08; tiempo controlado, duplicados y máximo dos líneas por préstamo | 13 | Verificada el 15 |
| T-06 | Researcher y User | T-03 | Normalización, rol válido, documento; HU-02/HU-05 | 13 | Verificada el 15 |
| T-07 | Autenticación y creación de laboratorista con fakes | T-06 | HU-01/HU-02; 401/403/409/422 y no exponer contraseña | 14 | Verificada el 16 con fakes; HTTP verificado el 18 en T-16 |
| T-08 | Casos de catálogo, fotografía e investigadores con fakes | T-04/T-06 | HU-03/HU-04/HU-05; filtros, paginación, bajas y duplicados | 14 | Verificada el 17 con fakes; integración HTTP verificada el 18 |
| T-09 | LendEquipmentService con fakes | T-05/T-08 | HU-06 completa: límite, vencidos, stock y atomicidad | 15 | Verificada el 17 con fakes; alta concurrente verificada en T-13 |
| T-10 | Devolución y consulta de vencidos | T-09 | HU-07/HU-09; devolución duplicada sin segundo incremento | 15 | Verificada con fakes: devolución el 17 y vencidos el 18; devolución HTTP y lector PostgreSQL verificados en T-13 |
| T-11 | Reporte agregado y puerto de lectura | T-09 | HU-10, ADR-004; snapshots permanecen tras renombrar | 16 | Verificada el 18 con fakes, PostgreSQL y HTTP; reporte estable después de renombrar y devolver |
| T-12 | Repositorios de equipo, investigador y usuario | T-02/T-08 | PostgreSQL real; mismos contratos que los fakes | 14 | Verificada el 17: repositorios, lectores y contratos compartidos |
| T-13 | Repositorio de préstamos, versión y transacción | T-12/T-09 | RNF-2: peticiones concurrentes 201/409; máximo por investigador | 15 | Verificada el 18 con PostgreSQL y HTTP |
| T-14 | Hash Argon2id y JWT firmado | T-07 | RNF-3: expiración, firma, rol, logs/respuestas sin secretos | 14 | Adaptadores verificados el 16; roles/expiración/respuestas/logs HTTP verificados el 18; TLS remoto pendiente |
| T-15 | Almacenamiento de fotos | T-08 | 2 MiB válido, exceso/tipo falso 422, descarga autorizada y clave null | 14 | Verificada el 18: PUT multipart real, 2 MiB/exceso, contenido falso, descarga autenticada y eliminación |
| T-16 | Las 21 operaciones HTTP y error uniforme | T-10/T-11/T-14/T-15 | RNF-4: matriz de rutas, roles, entradas y códigos | 16 | Verificada el 18: inventario exacto, permisos, validaciones, ciclo de préstamo y errores uniformes |
| T-17 | Composición y admin desde entorno | T-16 | Arranque desde DB vacía, admin inicia sesión, reinicio idempotente | 16 | Verificada el 18: base vacía aislada, login HTTP y repetición sin cambios |
| T-18 | CHECK de total positivo y rol en motor | T-02 | SQL inválido rechazado; modelo actualizado tras comprobarlo | 16 | Verificada el 16 en PostgreSQL |
| T-19 | Pruebas de dependencia y composición | T-17 | Artículos I-III; prueba falla al introducir dependencia prohibida | 16 | Verificada el 18: dependencias del núcleo y composición con mutaciones de prueba |
| T-20 | Compose con db, api y app | T-17 | RNF-5: un comando tras configurar entorno; DDL solo en API | 16 | Verificada el 19: db/api/app saludables, volumen nuevo y reinicio por HTTP |
| T-21 | Sembrador HTTP | T-17 | >=40 equipos, >=10 investigadores, >=15 préstamos, exactamente 3 líneas vencidas; restauración de reloj | 16 | Verificada el 19: 40/10/15, tres vencidos, reporte y restauración tras fallo |
| T-22 | React con todos los flujos | T-16 | Login, usuarios admin, catálogo/fotos, investigadores, préstamos, devolución, vencidos y reporte; estados vacíos/error | 16 | Pendiente |
| T-23 | Página pública de producto | - | Sin API; escritorio y 375 px; reglas/roles/vencidos, sin credenciales ni jerga del examen | 16 | Pendiente |

La línea base reservaba el 17 a verificación transversal, pero el atraso exige continuar desarrollo ese día según [remaining-blocks.md](remaining-blocks.md). El cierre aún requiere las 23 tareas, RNF-1 (5.000 equipos / 300 ms), al menos 15 archivos significativos de pruebas y congruencia entre producto y documentos. No se añaden tareas T-24 ficticias ni se omiten T-18 a T-23 por considerarlas opcionales.

## Matriz de las reglas

| Regla | Tareas | Prueba principal prevista |
|---|---|---|
| RN-1 | T-02/T-04/T-10/T-13/T-18 | EquipmentTest, SchemaConstraintsTest, ConcurrentReturnTest |
| RN-2 | T-05/T-09/T-13 | LoanTest, LendEquipmentServiceTest, ConcurrentResearcherLimitTest |
| RN-3 | T-09/T-10 | LendEquipmentServiceTest, OverdueContractTest |
| RN-4 | T-09/T-11 | FrozenReportTest |
| RN-5 | T-04/T-08/T-16 | RetirementTest, RouteContractTest |
| RN-6 | T-03/T-05/T-16/T-22 | DateRangeTest, LoanContractTest, revisión de idioma |

## Registro de evidencia

Para cada cierre se añade una entrada a `docs/evidence/` con fecha real, tarea, referencia al commit, entorno, comando reproducible, resultado esperado/obtenido y limitaciones. No adjuntar credenciales, respuestas con datos personales ni logs completos sin revisar. Las evidencias verificadas no certifican las tareas aún pendientes.
