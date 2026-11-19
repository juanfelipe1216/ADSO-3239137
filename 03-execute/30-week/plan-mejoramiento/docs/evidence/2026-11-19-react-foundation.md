# B13 · Base React

El 19 de noviembre se creó la aplicación con React 19.3.1 y Vite 8.3.8, dependencias fijadas en package-lock.json. Hay ingreso real mediante el contrato HTTP, sesión en memoria, expiración de ocho horas según respuesta, cierre de sesión y cliente que conserva code/message/details. No se guarda el token en localStorage. Las vistas operativas completas siguen en B15/T-22.

Pruebas: npm ci, npm test y npm run build desde app. Resultado: cinco pruebas aprobadas en dos archivos; compilación de producción aprobada. Se comprueban ingreso, limpieza de contraseña/cierre, error de credenciales, Bearer/204, errores de negocio y fallos de red/respuesta inválida.

Este primer avance valida la aplicación y su compilación; la prueba de Compose con los tres servicios corresponde al siguiente avance de B13. Commit: git log --grep="feat(app): add React login and API client".
