# ADR-005: equivalencias de laboratorio

- Estado: propuesta explícita, 12 de noviembre de 2026; límites y categorías provienen del acta, el formato de referencia es decisión de diseño.
- Contexto: copiar las reglas de biblioteca conservaría erróneamente tres libros, catorce días e ISBN. Debe construirse una especificación propia equivalente.
- Decisión: `Book -> Equipment`, `Member -> Researcher`, `librarian -> lab_technician`, `title -> name`, `author -> manufacturer`, `isbn -> reference`, `copies -> units`. `Loan` y `LoanItem` conservan su papel. Cinco categorías: medición, óptica, electrónica, química y seguridad. Máximo dos líneas nuevas y dos pendientes en total; cinco días de plazo.
- Alternativas: ISBN numérico de 13 dígitos (propio de libros); serial individual y tabla por unidad (función no pedida); quitar fabricante sin analizar la equivalencia de búsquedas (pierde un campo equivalente del contrato).
- Consecuencias: se acuerda referencia de 1-64 caracteres ASCII, mayúsculas, dígitos y separadores `._/-`, con primer carácter alfanumérico; única entre activos e inmutable al editar. Se mantienen búsqueda por nombre/fabricante y fotografía opcional. Estas precisiones se registran como decisiones, no como citas literales del acta.
- Evidencia requerida: las 10 HU, 6 RN, 5 RNF, 21 operaciones y 23 tareas se trazan al dominio nuevo; no quedan reglas de biblioteca en código de producción.
