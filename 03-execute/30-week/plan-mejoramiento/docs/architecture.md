# Arquitectura

Diseño objetivo. Al 18 de noviembre están implementados el arranque Laravel, la migración PostgreSQL, los objetos del dominio y los casos de login/creación de laboratoristas con fakes (T-01 a T-07). T-08 verifica servicios de investigadores, catálogo y fotografías con fakes; T-09/T-10 verifican alta de préstamos, devolución, rollback y consulta de vencidos con fakes. También están verificados los adaptadores reales de hash/JWT, almacenamiento local y repositorios/lectores PostgreSQL de T-12, compuestos únicamente en LabServiceProvider. T-13 verifica persistencia de préstamos y dos operaciones HTTP concurrentes. B11 conecta las 21 operaciones y verifica el contrato contra los adaptadores reales. Monolito modular con arquitectura hexagonal. React consumirá HTTP; Laravel adaptará peticiones a casos de uso; PostgreSQL persistirá el negocio. La página pública no consumirá la API.

```mermaid
flowchart LR
    O[Administrador y laboratorista] --> R[app: React]
    V[Visitante] --> P[page: HTML y CSS]
    R --> H[Adaptador HTTP Laravel]
    T[tool: sembrador HTTP] --> H
    H --> A[Application: servicios y puertos]
    A --> D[Domain: reglas puras]
    DB[Adaptador PostgreSQL] -. implementa .-> A
    S[Hash, JWT, reloj, imágenes] -. implementan .-> A
    DB --> PG[(PostgreSQL 16)]
```

## Estructura prevista

```text
api/
  composer.json
  artisan
  bootstrap/app.php
  app/Providers/LabServiceProvider.php
  src/
    Domain/          Equipment, Loan, LoanItem, Researcher, User, ValueObjects
    Application/     Ports, Services, DTOs
    Adapters/        Http, Persistence, Security, Storage, Time
  database/migrations/
  routes/api.php
  tests/             Unit, Application, Integration, Contract, Architecture
app/src/             api, auth, components, features
page/                index.html, assets
infra/               compose.yaml, Dockerfiles, configuración de servicios
tool/                sembrador, concurrencia y carga
```

`api/composer.json` se ubica directamente en la carpeta exigida; debe declarar `laravel/framework`. No se usará una subcarpeta `backend` que oculte al evaluador el producto. `Domain` no depende de `Application`; `Application` no importa `Adapters`; los adaptadores implementan interfaces de aplicación. Solo el proveedor compone instancias de infraestructura.

## Puertos y agregados

Puertos previstos: `EquipmentRepository`, `ResearcherRepository`, `UserRepository`, `LoanRepository`, `ReportReader`, `TransactionManager`, `Clock`, `PasswordHasher`, `TokenIssuer`, `CoverStorage`, `IdGenerator`. Ninguna firma expone un modelo Eloquent, un Request, Builder o conexión PDO.

`Equipment` garantiza existencias en rango; `Loan` controla líneas, fecha de vencimiento y devolución única; `Researcher` define nombre/documento; `User` normaliza usuario y rol. La aplicación verifica reglas que cruzan agregados, como el total pendiente y los vencidos del investigador.

Los objetos del dominio implementados son inmutables: retirar, restituir, editar, dar de baja y devolver generan una nueva instancia válida. El servicio de aplicación deberá guardar explícitamente la instancia resultante dentro de la transacción. El dominio recibe instantes explícitos; no consulta el reloj del sistema ni conoce el puerto `Clock`. La normalización de usuario se comparte mediante `User::normalizeUsername`; el hash es privado y queda excluido de su representación JSON y depuración. Esto no sustituye las pruebas posteriores de seguridad HTTP y logs.

Cada servicio realiza una acción: `LoginService`, `CreateLabTechnicianService`, `CreateEquipmentService`, `UpdateEquipmentService`, `SearchEquipmentService`, `RetireEquipmentService`, `UploadCoverService`, `RemoveCoverService`, `CreateResearcherService`, `LendEquipmentService`, `ReturnItemService`, `ListOverdueService`, `MostBorrowedReportService` y consultas de detalle/listado.

## Flujo de préstamo y concurrencia

T-09 implementa `LendEquipmentService` con puertos propios. `TransactionManager::forResearcher` define una transacción que serializa las operaciones del mismo investigador antes del callback y revierte cualquier `Throwable`. El servicio lee Clock y pendientes dentro de ese callback, valida todos los equipos activos antes de vencidos/límite/stock, escribe equipos en orden de id y agrega la cabecera con sus líneas congeladas. Conserva el orden de líneas solicitado en la respuesta. `DomainViolation::context` transporta el dato de negocio `active_loans` al exceder el límite; el adaptador HTTP lo convierte en `details`, sin importar HTTP en el núcleo.

En T-13 el proveedor conecta los servicios con PostgreSQL. La transacción adquiere un bloqueo asesor asociado al UUID del investigador. El alta usa el sujeto del JWT verificado para registrar el operador.

T-12 verifica escrituras de catálogo condicionadas por `xmin`, id, referencia inmutable y estado activo. `xmin` identifica la transacción que escribió la fila; no es un contador por sentencia dentro de una misma transacción. La prueba de versión usa escrituras confirmadas por separado. No se reutilizará un testigo antiguo para sucesivas modificaciones del mismo equipo dentro de una transacción. T-13 verifica el conflicto de última unidad por HTTP.

Los lectores de T-12 calculan total y página en una sola sentencia para compartir la misma instantánea. Ordenan por nombre con collation `C` e id para desempatar, conservan el total en páginas vacías y escapan `%`, `_` y `!` en búsquedas literales. Las entradas son parámetros SQL; las únicas piezas SQL compuestas proceden de constantes internas de los adaptadores.

1. HTTP autentica, autoriza y valida estructura.
2. La aplicación abre una transacción y serializa operaciones del mismo investigador mediante un bloqueo transaccional asesor.
3. Consulta pendientes y vencidos bajo ese bloqueo; carga equipos activos por lote y captura sus `xmin`.
4. Dominio calcula el préstamo, snapshots, cinco días y descuentos.
5. El adaptador actualiza cada equipo condicionado a id, `xmin`, activo y disponibilidad, con orden estable de ids.
6. Inserta cabecera y líneas. Si un descuento falla, rollback de toda la transacción y 409.
7. Confirma y devuelve 201. El testigo no se reutiliza en otra solicitud ni se expone al cliente.

El bloqueo por investigador también se aplica a devoluciones para mantener consistentes sus pendientes. No bloquea todos los investigadores. La devolución condiciona `returned_at IS NULL` e incrementa existencias dentro de la misma transacción; si otra devolución ganó, responde 409. Edición de total, baja y préstamos deben compartir una estrategia de escritura que respete el stock y la versión; se comprobarán sus carreras en integración.

T-10 verifica `ReturnItemService` con fakes. La lectura inicial identifica al investigador inmutable del préstamo; después del bloqueo se vuelve a leer el préstamo y se comprueba la línea solicitada. `LoanRepository::markReturned` debe modificar únicamente `returned_at`, condicionado a pertenencia y ausencia de devolución previa. La escritura de stock usa la versión del equipo; ambas escrituras comparten transacción y cualquier error revierte las dos. `LoanItemData` unifica la representación de líneas en alta y devolución. T-13 verifica la devolución en PostgreSQL y las respuestas 200 y 409.

`ListOverdueService` define el puerto `OverdueReader`: devuelve líneas sin devolver con `due_at < now`, ordenadas por vencimiento e id de línea, con nombre congelado del equipo y nombre del investigador. El mismo instante UTC de Clock se usa para selección y cálculo; no hay columna de estado ni días. `LoanItem::daysLate` calcula días completos con diferencia de instantes UTC conservando microsegundos, para no adelantar el día cuando faltan fracciones de segundo. Servicio, fake y lector PostgreSQL están probados. La ruta GET de vencidos está conectada y probada en T-16.

T-11 verifica `MostBorrowedReportService` con `ReportReader` y `MostBorrowedRow`. El servicio autoriza al administrador antes de validar fechas o consultar. `PostgresReportReader`, compuesto únicamente en `LabServiceProvider`, agrupa `loan_item` por id de equipo, nombre y categoría congelados y filtra por `loan.loaned_at >= inicio AND < fin_exclusivo`. No une catálogo, investigadores ni usuarios. Cuenta también devueltas; ordena cantidad descendente, nombre/categoría con collation `C` e id para desempatar. `FrozenReportTest` aplica las mismas expectativas al fake y a PostgreSQL y modifica realmente el catálogo para verificar la independencia histórica. El endpoint y su autorización por token están probados en B11, incluido préstamo, cambio de nombre, devolución y comparación del reporte.

`SearchLoansService` depende de `LoanReader`, `LoanFilter`, `Pagination` y `Clock`; `GetLoanService` usa el repositorio. El lector PostgreSQL obtiene total, página y líneas congeladas en una sola sentencia, sin consultas por cada préstamo. Calcula el estado con el mismo instante que utiliza el DTO; ordena fecha descendente e id, incluye el día final completo y conserva el total en páginas vacías. El estado no se persiste.

`Access` verifica JWT y rol antes de cargar el recurso. Los controladores validan estructura y llaman servicios; no componen adaptadores. PUT multipart usa el parser de PHP 8.4 cuando el servidor no ha poblado los archivos. Las fotografías salen únicamente por su ruta autenticada; se deshabilitaron las rutas automáticas de almacenamiento del framework.

Errores propios del dominio/aplicación se convierten al contrato únicamente en HTTP. Los inesperados devuelven un mensaje genérico; el registro guarda la clase de excepción sin mensaje, argumentos, SQL ni traza, para evitar filtraciones de credenciales. Esto limita el diagnóstico del registro y requiere reproducción controlada para investigar fallos. No se añaden colas, Redis, microservicios ni almacenamiento de reportes.
