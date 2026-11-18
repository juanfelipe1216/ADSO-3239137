# B10 · Persistencia y rollback

El 18 de noviembre, LoanPersistenceTest ejercitó los servicios de alta y devolución con los puertos resueltos por LabServiceProvider y PostgreSQL 16. Tras insertar un préstamo y descontar la unidad, una excepción forzada revirtió cabecera, líneas y existencias. Un intento posterior recuperó el operador, la categoría congelada y el instante con microsegundos. La consulta de vencidos excluyó el vencimiento exacto e incluyó la línea un microsegundo después. La devolución la retiró de la consulta y restituyó una unidad.

Desde la raíz del proyecto, reconstruir con `docker compose --env-file .env -f infra/compose.yaml up --build -d` y ejecutar `docker compose --env-file .env -f infra/compose.yaml exec -T api composer test`. Pasaron 288 casos y 1.071 aserciones. El commit se identifica por el título `feat(loans): persist loans and returns in PostgreSQL transactions`. T-13 sigue abierta hasta comprobar las tres carreras.
