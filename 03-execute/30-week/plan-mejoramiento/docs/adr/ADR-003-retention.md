# ADR-003: conservar el histórico

- Estado: propuesta, 12 de noviembre de 2026.
- Contexto: préstamos, devoluciones e investigadores sostienen la trazabilidad del laboratorio. El borrado de socios agregado al examen anterior incumplía CA-05.3.
- Decisión: equipos con `deleted_at`; catálogo filtra activos. Bloquear baja con préstamos abiertos. Sin rutas para borrar investigadores, usuarios, categorías, préstamos o devoluciones. La referencia es única solo entre equipos activos.
- Alternativas: borrado físico con cascadas (destruye hechos); `active` sin instante (pierde fecha de baja); permitir borrado de investigadores sin préstamos (función fuera del contrato).
- Consecuencias: una referencia de un equipo dado de baja puede volver a registrarse con nuevo UUID. Las FK históricas siguen apuntando al equipo anterior. El binario de la foto es el único dato del catálogo que se retira físicamente.
- Evidencia requerida: CA-03.5, CA-05.3, CA-06.7 y prueba de FK RESTRICT; el histórico sigue visible después de la baja.
