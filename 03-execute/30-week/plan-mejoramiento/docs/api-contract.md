# Contrato HTTP de lab-equipment

Contrato previsto; rutas aún no implementadas. Base única `/api/v1`; las rutas de la tabla son relativas a ella. Salud significa `/api/v1/health`: no se añade un alias `/up` ni una segunda ruta pública de negocio. JSON UTF-8, nombres `snake_case`, UUID v4 y fechas ISO 8601 UTC terminadas en `Z`.

## Convenciones

- `Authorization: Bearer <token>` excepto login y salud.
- Listados paginados: `page` entero >= 1, `size` entre 1 y 50, predeterminado 20. Valores inválidos: 422, sin recortar silenciosamente. Respuesta: `{ "items": [], "page": 1, "size": 20, "total": 0 }`.
- Filtros `from` y `to`: fechas `YYYY-MM-DD`. Ambos límites incluyen el día completo UTC: internamente `>= inicio` y `< día posterior al fin`.
- Campos requeridos ausentes, cadenas vacías, UUID con formato inválido y campos no admitidos: 422. UUID válido pero inexistente: 404.
- Un 403 se evalúa antes de cargar el objeto. No se revela su existencia a un rol sin acceso.
- PUT reemplaza los campos editables definidos. No acepta `available_units`, operador, fechas ni datos congelados aportados por el cliente.

Todos los errores, incluidos 401, 403, 404, 405, 413, 422 y 500, usan esta forma; los errores inesperados no incluyen excepciones, rutas, SQL ni secretos:

```json
{
  "code": "EQUIPMENT_NOT_AVAILABLE",
  "message": "El equipo no tiene unidades disponibles.",
  "details": [{ "field": "items[0].equipment_id", "issue": "Sin unidades disponibles." }]
}
```

`details` siempre es una lista, puede estar vacía. Mensajes e incidencias en español; códigos y nombres de campo en inglés.

## Las 21 operaciones equivalentes

`A`: admin. `L`: lab_technician. Solo las dos primeras son públicas.

| # | Método | Ruta | Rol | Éxito |
|---|---|---|---|---|
| 1 | POST | /auth/login | público | 200 token |
| 2 | GET | /health | público | 200 status |
| 3 | POST | /users | A | 201 usuario |
| 4 | GET | /categories | A,L | 200 categorías |
| 5 | GET | /equipment | A,L | 200 página |
| 6 | GET | /equipment/{id} | A,L | 200 equipo |
| 7 | POST | /equipment | A | 201 equipo |
| 8 | PUT | /equipment/{id} | A | 200 equipo |
| 9 | DELETE | /equipment/{id} | A | 204 sin cuerpo |
| 10 | PUT | /equipment/{id}/cover | A | 200 equipo con clave |
| 11 | DELETE | /equipment/{id}/cover | A | 204 sin cuerpo |
| 12 | GET | /equipment/{id}/cover | A,L | 200 binario JPG/PNG |
| 13 | GET | /researchers | A,L | 200 página |
| 14 | GET | /researchers/{id} | A,L | 200 investigador |
| 15 | POST | /researchers | A,L | 201 investigador |
| 16 | POST | /loans | A,L | 201 préstamo |
| 17 | GET | /loans | A,L | 200 página |
| 18 | GET | /loans/{id} | A,L | 200 préstamo |
| 19 | POST | /loans/{loan_id}/items/{item_id}/return | A,L | 200 línea |
| 20 | GET | /loans/overdue | A,L | 200 items |
| 21 | GET | /reports/most-borrowed | A | 200 reporte |

Registrar `/loans/overdue` antes de la ruta con id. No hay edición/borrado de investigador ni de préstamo, CRUD de categorías, registro público, recuperación de contraseña o endpoint para cambiar el reloj.

## Peticiones y respuestas

### Autenticación, salud y categorías

Login recibe `username` y `password`. Usuario normalizado mediante trim y minúsculas; contraseña sin transformaciones. Responde `access_token`, `token_type: "Bearer"`, `expires_in: 28800` y `role`. JWT HS256 con `sub`, `role`, `iat`, `exp`; validar firma, algoritmo y expiración. Rechazar algoritmo `none` o distinto al configurado.

Salud devuelve exactamente `{"status":"ok"}`. Categorías devuelve `{"items":[{"id":"UUID","name":"medición"}, ...]}` con las cinco entradas ordenadas por nombre; no lleva paginación.

Crear usuario recibe `username` (1-120), `password` (12-128 caracteres, sin truncamiento) y `role` igual a `lab_technician`. Devuelve únicamente `id`, `username`, `role`. La política de longitud es una decisión de seguridad de implementación y no habilita funciones nuevas.

### Equipos

Crear recibe `name` (1-250 caracteres recortados), `manufacturer` (1-200), `reference` (1-64), `category_id` y `total_units` entero >= 1. La referencia se recorta y pasa a mayúsculas ASCII; debe cumplir `^[A-Z0-9][A-Z0-9._/-]{0,63}$`. El contrato no exige ISBN ni serial individual. Categoría inexistente: 404.

Editar recibe todos estos campos excepto `reference`. Disponibles se ajusta mediante `new_total - old_total` en la misma operación; si hay más prestados que el nuevo total, responde 422 sin cambiar datos. GET listado admite `q`, `category_id`, `page`, `size`, y busca por texto parcial sin distinguir mayúsculas en nombre o fabricante.

```json
{
  "id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
  "name": "Multímetro digital",
  "manufacturer": "Fabricante de demostración",
  "reference": "LAB-MUL-001",
  "category": { "id": "33333333-3333-4333-8333-333333333333", "name": "electrónica" },
  "total_units": 3,
  "available_units": 2,
  "cover_key": null
}
```

Fotografía: `multipart/form-data`, campo `file`, hasta 2.097.152 bytes; MIME obtenido del contenido `image/jpeg` o `image/png`. Archivo inválido o sobre el límite funcional: 422. El límite del servidor se configura por encima de este umbral para que la validación devuelva el cuerpo contratado. Descarga autenticada con `Content-Type` correcto; inexistente: 404. Quitar una fotografía ya ausente de un equipo activo es idempotente, 204. `cover_key` nunca es una URL ni ruta.

### Investigadores

Crear recibe `full_name` (1-200) y `document_number` (1-30), ambos recortados y no vacíos. La unicidad compara el documento recortado. Respuesta: `id`, `full_name`, `document_number`. El detalle agrega `active_loans`, que cuenta líneas sin devolver, no cabeceras. Listado admite `q`, `page`, `size`, orden nombre e id; busca parcialmente en nombre o documento.

### Préstamos y devoluciones

Petición de alta:

```json
{
  "researcher_id": "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",
  "items": [{ "equipment_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa" }]
}
```

Respuesta incluye `id`, `researcher_id`, `loaned_at`, `status`, `items`. Cada línea incluye `id`, `equipment_id`, `equipment_name`, `reference`, `category_name`, `due_at`, `returned_at`. Por ejemplo, préstamo `2026-11-12T23:00:00Z` vence `2026-11-17T23:00:00Z`.

Validaciones en orden: estructura y duplicados; existencia de investigador/equipos activos; vencidos; límite de pendientes; disponibilidad. Un conflicto revierte todo. `RESEARCHER_LIMIT_EXCEEDED` informa el conteo actual en `details`, sin exponer otras personas.

Listado admite `from`, `to`, `status`, `researcher_id`, `page`, `size`. Fechas opcionales individuales restringen solo su extremo; si ambas existen se valida orden. Valores de `status`: `open`, `overdue`, `closed`. Devuelve la misma representación de préstamo, orden por `loaned_at` descendente e id ascendente.

Devolución no recibe datos; la línea debe pertenecer al préstamo solicitado, de lo contrario 404. Devuelve la línea completa con `returned_at`. Si ya estaba devuelta, 409 sin modificar existencias.

Vencidos devuelve `{"items": [...]}` con `loan_id`, `item_id`, `equipment_name`, `researcher_name`, `due_at`, `days_late`, orden por vencimiento e id. Solo se incluyen líneas sin devolver con `due_at < now`; días completos transcurridos calculados en UTC.

### Reporte

`from` y `to` obligatorios. Devuelve `from`, `to`, `rows`; cada fila tiene `equipment_id`, `equipment_name`, `category_name`, `times_borrowed`. Agrupa exclusivamente los valores congelados de las líneas. No agrega columnas de investigador, laboratorista, stock actual o fabricante.

## Códigos

| Código | HTTP | Situación |
|---|---:|---|
| INVALID_CREDENTIALS | 401 | Inicio de sesión incorrecto |
| UNAUTHENTICATED | 401 | Token ausente, inválido o vencido |
| FORBIDDEN_ROLE | 403 | Rol insuficiente |
| NOT_FOUND | 404 | Recurso inexistente o inactivo |
| USERNAME_TAKEN | 409 | Usuario normalizado repetido |
| REFERENCE_TAKEN | 409 | Referencia activa repetida |
| DOCUMENT_TAKEN | 409 | Documento repetido |
| EQUIPMENT_HAS_OPEN_LOANS | 409 | Baja con pendientes |
| EQUIPMENT_NOT_AVAILABLE | 409 | Stock agotado o conflicto de versión al prestar |
| RESEARCHER_HAS_OVERDUE | 409 | Investigador con vencidos |
| ITEM_ALREADY_RETURNED | 409 | Segunda devolución |
| RESEARCHER_LIMIT_EXCEEDED | 422 | Se excederían dos pendientes |
| DUPLICATE_EQUIPMENT_IN_LOAN | 422 | Equipo repetido |
| VALIDATION_ERROR | 422 | Entrada inválida |
| INVALID_DATE_RANGE | 422 | Fechas invertidas |
| METHOD_NOT_ALLOWED | 405 | Método sin operación |
| PAYLOAD_TOO_LARGE | 413 | Límite general de transporte |
| INTERNAL_ERROR | 500 | Error inesperado sin detalles internos |

Los 14 códigos del negocio original conservan equivalentes; los cuatro códigos adicionales de infraestructura normalizan errores HTTP, sin añadir operaciones. CORS solo admite el origen configurado en `CORS_ORIGIN`. Para acceso remoto se exige HTTPS; el uso de HTTP en loopback local se registra como limitación de desarrollo y no como demostración de seguridad en producción.
