# Preparación de sustentación individual

Fecha del acta: 26 de noviembre de 2026, desde las 18:00. Ejecutar el sistema y explicar decisiones forma parte del cumplimiento; no basta presentar código generado o una página funcionando.

## Recorrido que se practicará al cerrar el producto

1. Explicar el objetivo y los roles en un minuto. Diferenciar investigador de cuenta de acceso.
2. Levantar el entorno desde las instrucciones; señalar dónde se crean esquema, categorías y admin.
3. Crear un investigador, prestar dos equipos y mostrar el bloqueo de un tercero.
4. Devolver una línea y demostrar que devolverla otra vez no aumenta el stock.
5. Consultar los tres vencidos de demostración y explicar el Clock aislado y su restauración.
6. Renombrar un equipo y mostrar que el reporte histórico conserva el nombre anterior.
7. Ejecutar la prueba de dos operadores sobre la última unidad y leer 201/409 y estado final.
8. Abrir una HU y seguir sus enlaces a regla, caso de uso, prueba y commit.
9. Mostrar la página sin API y distinguirla de la aplicación operativa.

## Preguntas que debe poder responder el aprendiz

- ¿Por qué el dominio no importa Laravel y qué prueba lo impide?
- ¿Qué garantiza el CHECK y qué necesita una transacción?
- ¿Por qué proteger stock no basta para proteger el máximo por investigador?
- ¿Qué es el testigo xmin y por qué no es una columna nueva ni un identificador permanente?
- ¿Por qué no se almacena `status` y qué pasa exactamente en `due_at`?
- ¿Por qué el reporte puede tener dos filas del mismo equipo?
- ¿Por qué no existe borrado de investigadores, registro público ni recuperación de contraseña?
- ¿Cómo se sabe que hay quince archivos de pruebas útiles y que efectivamente pasaron?

## Ensayo de cambio en vivo

Practicar en una rama local desechable: cambiar un texto de validación o un criterio de orden, localizar contrato/prueba/código, observar prueba fallida, corregir y volver a ejecutar. Si el instructor pide alterar un límite de negocio, identificar todas sus dependencias antes de editar. No mezclar el ensayo con la entrega ni alterar fechas del historial.

Reservar de 10 a 15 minutos al final de cada sesión de implementación para que el aprendiz explique el cambio con sus palabras. Registrar dudas reales para resolverlas en la siguiente sesión.
