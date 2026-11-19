# Estrategia de pruebas y entrega

Inventario de cobertura objetivo. Al 19 de noviembre existen **42 archivos con 331 casos y 1.690 aserciones aprobadas**; cada cierre comprobado está en [tasks.md](tasks.md) y `evidence/`. La tabla incluye pruebas implementadas y otras pendientes, y sus nombres previstos no sustituyen la evidencia real. El mínimo del acta es 15 archivos; se proponen comportamientos diferentes, sin subdividir aserciones artificialmente.

| Archivo previsto | Qué debe demostrar |
|---|---|
| Unit/ReferenceTest.php | Normalización, límites y caracteres de referencia |
| Unit/QuantityTest.php | Enteros positivos y entradas inválidas |
| Unit/DateRangeTest.php | Rango invertido y días UTC incluidos |
| Unit/EquipmentTest.php | Descuento, devolución y cambio de total sin romper stock |
| Unit/LoanTest.php | Una/dos líneas, duplicados, cinco días y estados con Clock falso |
| Unit/UserTest.php | Nombre normalizado y rol válido |
| Application/LoginServiceTest.php | Credenciales contra fakes, error genérico y token |
| Application/CreateLabTechnicianServiceTest.php | Duplicados y prohibición de crear admin |
| Application/EquipmentServicesTest.php | Crear/buscar/editar/baja con fakes |
| Application/ResearcherServicesTest.php | Crear/buscar investigador y documento único |
| Application/LendEquipmentServiceTest.php | Límite acumulado, vencidos, disponibilidad y rollback lógico |
| Application/ReturnItemServiceTest.php | Devolución única y existencias |
| Application/ListOverdueServiceTest.php | Comparación estricta de instantes, orden, días completos y devolución seguida de consulta |
| Application/MostBorrowedReportServiceTest.php | Rango y lector de snapshots |
| Integration/RepositoryContractTest.php | Repositorios reales equivalentes a fakes |
| Integration/SchemaConstraintsTest.php | FK/UNIQUE/CHECK, columnas exactas, sin DEFAULT |
| Integration/FrozenReportTest.php | Renombrar/recategorizar sin alterar pasado; varias filas congeladas |
| Integration/RetirementTest.php | Baja bloqueada con pendientes, referencia reutilizable y FK histórica |
| Integration/CoverStorageTest.php | Contenido válido/falso, 2 MiB/exceso, clave opaca y eliminación |
| Integration/ConcurrentLoanTest.php | Dos procesos y última unidad: un 201, un 409 y un solo préstamo |
| Integration/ConcurrentReturnTest.php | Dos devoluciones: un 200, un 409, una sola restitución |
| Integration/ConcurrentResearcherLimitTest.php | Dos altas con equipos distintos no superan máximo dos |
| Contract/RouteContractTest.php | Inventario exacto de 21 rutas, sin endpoints de borrado extra |
| Contract/AuthenticationContractTest.php | Token ausente, manipulado, vencido, rol y cuerpo uniforme |
| Contract/LoanContractTest.php | Alta, detalle, listado, devolución, snapshots y errores |
| Contract/OverdueContractTest.php | Vencidos, orden, acceso y límite exacto de fecha |
| Contract/ReportContractTest.php | Rango, vacío, agrupación y privacidad |
| Architecture/DependencyRulesTest.php | Dependencias internas y único punto de composición |

Se agregarán pruebas React para autorización visual, errores del contrato, formularios, paginación y recorridos. Los archivos de ejemplo del framework no cuentan como evidencia de requisitos. La suite debe verificar además el caso de préstamo de dos líneas cuando la segunda no tiene stock: ningún descuento ni cabecera parcial.

B11 incorpora `CatalogHttpTest`, `LoanHttpTest`, `HttpBoundaryTest` y `CoverHttpTest`: flujo HTTP contra PostgreSQL, las 19 operaciones privadas sin token, permisos admin, inventario exacto de 21 operaciones, fechas/estados/páginas, errores 413/500 y registro saneado, origen CORS fijo y PUT multipart en un proceso PHP independiente. Las pruebas limpian sus registros y fotos. Los administradores son fixtures, por lo que no sustituyen T-17.

## Verificaciones integradas

- **T-21:** por API, verificar cantidades de equipos, investigadores y préstamos; tres líneas vencidas; reporte no vacío; ejecutar con un fallo inducido y comprobar restauración del reloj. No falsear el resultado con datos de respuesta estáticos.
- **RNF-1:** sembrar 5.000 equipos por API en entorno aislado, medir búsquedas sin calentamiento y con calentamiento, varias categorías/textos/páginas; guardar configuración, p50, p95, máximo y errores. Un resultado superior a 300 ms se investiga antes de dar por cumplido.
- **RNF-2:** procesos/conexiones independientes, arranque simultáneo y observación de estado final. Una prueba secuencial no demuestra concurrencia.
- **RNF-3:** revisar respuestas y logs de fallos con entradas sintéticas identificables; no publicar la contraseña usada. Autorización servidor en todas las rutas, no solo ocultar botones.
- **RNF-4:** validar forma `code/message/details` en rutas de negocio, errores del framework y método inexistente.
- **RNF-5:** nuevo volumen aislado, un comando documentado, categorías/admin operativos y reinicio que no duplique registros. Nunca borrar volúmenes del usuario para demostrarlo.
- **T-22/T-23:** 375 px y escritorio, teclado, foco y etiquetas; interfaz sin desbordamiento; página usable con API apagada. Todos los selectores deben permitir encontrar registros fuera de los primeros 50.

## Lista final

- [ ] Las 10 HU, 6 RN, 5 RNF y 23 tareas tienen evidencia.
- [ ] Al menos 15 archivos de pruebas con aserciones reales pasan.
- [ ] Sin tablas de ejemplo, timestamps de auditoría ni atributos fuera del modelo.
- [ ] Sin `.env`, secretos, `node_modules`, `vendor` o resultados locales sensibles en nuevos commits.
- [ ] Cuatro o más commits sustantivos por subcarpeta y ocho fechas reales.
- [ ] Cambios de docs distribuidos y congruentes con el código.
- [ ] README inicia con la frase exigida y el acta A03 está adjunta.
- [ ] Ruta del fork y SHA remoto de entrega verificados antes del 19 a las 18:00.
- [ ] Proyecto formativo revisado por separado; no se confunde con lab-equipment.
- [ ] El aprendiz puede ejecutar, explicar decisiones y hacer un cambio en vivo.

No se garantiza una calificación a partir de esta lista; la evaluación corresponde al instructor. No se afirmará que un trabajo es original por cambiar nombres: el diseño debe responder al dominio, y las decisiones y pruebas deben poder explicarse.

El 19 de noviembre B15 eleva las pruebas React a 31 en cuatro archivos (catálogo, operaciones, login y cliente HTTP) y registra un recorrido real en un entorno Compose aislado (docs/evidence/2026-11-19-react-flows.md). El 19 de noviembre B13 añade cinco pruebas React/cliente HTTP en dos archivos (npm test desde app), compilación de producción y node tool/verify-compose.mjs desde la raíz. Esta última levanta un proyecto Compose temporal con volúmenes nuevos, comprueba frontend, API, login y categorías, reinicia y repite; luego elimina exclusivamente los recursos temporales. Requiere Node 24, Docker Compose compatible con !reset/!override y puerto 8082 libre (configurable con VERIFY_APP_PORT).

B14: node --test tool/clock-scope.test.mjs verifica restauración ante éxito y fallo. node tool/run-demo.mjs seed comprueba 40 equipos, 10 investigadores, 15 préstamos, tres vencidos y reporte de 15 solicitudes. node tool/run-demo.mjs performance crea 5.000 equipos por API y mide 168 búsquedas: p50 65,40 ms, p95 85,20 ms, máximo 142,81 ms y cero errores. Las muestras completas y limitaciones están en evidence/2026-11-19-seed-performance.md.
