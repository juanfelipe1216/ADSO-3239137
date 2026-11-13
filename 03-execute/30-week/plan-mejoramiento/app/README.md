# Aplicación de operación

Estado inicial: React pendiente. La interfaz debe permitir operar todos los flujos del [spec](../docs/spec.md), con textos en español y nombres de código en inglés.

| Pantalla | Operación y permiso |
|---|---|
| Ingreso | Credenciales, error genérico y sesión de ocho horas |
| Catálogo | Buscar/paginar para ambos roles; crear/editar/baja/fotografía solo admin |
| Investigadores | Crear, buscar y consultar pendientes; sin eliminar |
| Préstamos | Registrar hasta dos equipos, listar y consultar detalle |
| Devolución | Acción por línea, confirmación de resultado y conflicto duplicado |
| Vencidos | Líneas pendientes ordenadas y días de retraso |
| Reporte | Rango y filas congeladas, solo admin |
| Laboratoristas | Alta de usuarios con ese rol, solo admin |

Cada vista tendrá estados de carga, vacío, error y éxito. Los selectores deberán buscar/paginar más allá de 50 registros. El cliente HTTP usará `/api/v1`, token Bearer y `code/message/details`; la autorización definitiva corresponde al servidor. Fotografías autenticadas se obtendrán con token, sin publicar su ubicación interna.

T-22 solo se completa con recorrido de extremo a extremo y pruebas. El README no constituye una aplicación ejecutable.
