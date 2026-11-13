# ADR-004: reporte estable

- Estado: propuesta, 12 de noviembre de 2026.
- Contexto: cambiar el nombre o categoría del catálogo no debe reescribir lo que se entregó antes.
- Decisión: cada línea guarda nombre, referencia y categoría del instante de préstamo. El reporte agrupa en SQL por `equipment_id`, `equipment_name`, `category_name` de esas líneas. No une el catálogo para obtener los rótulos y no persiste un segundo reporte.
- Alternativas: catálogo vivo (cambia el pasado); último nombre del rango (altera resultados previos); tabla resumen (doble fuente de verdad).
- Consecuencias: un equipo puede aparecer en varias filas si cambió de nombre/categoría entre préstamos. Las devoluciones no reducen el número de solicitudes. Los datos de investigador y operador no se incluyen.
- Evidencia requerida: prestar, renombrar y recategorizar; el reporte del periodo anterior conserva las cadenas originales. Verificar además rango vacío, invertido, límites UTC y acceso de laboratorista denegado.
