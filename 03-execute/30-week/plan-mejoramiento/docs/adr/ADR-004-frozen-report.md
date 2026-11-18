# ADR-004: reporte estable

- Estado: aceptada y verificada en servicio/lector PostgreSQL el 18 de noviembre de 2026; propuesta original del 12. HTTP pendiente.
- Contexto: cambiar el nombre o categoría del catálogo no debe reescribir lo que se entregó antes.
- Decisión: cada línea guarda nombre, referencia y categoría del instante de préstamo. El reporte agrupa en SQL por `equipment_id`, `equipment_name`, `category_name` de esas líneas. No une el catálogo para obtener los rótulos y no persiste un segundo reporte.
- Alternativas: catálogo vivo (cambia el pasado); último nombre del rango (altera resultados previos); tabla resumen (doble fuente de verdad).
- Consecuencias: un equipo puede aparecer en varias filas si cambió de nombre/categoría entre préstamos. Las devoluciones no reducen el número de solicitudes. Los datos de investigador y operador no se incluyen.
- Evidencia requerida: prestar, renombrar y recategorizar; el reporte del periodo anterior conserva las cadenas originales. Verificar además rango vacío, invertido, límites UTC y acceso de laboratorista denegado.
- Evidencia obtenida: `MostBorrowedReportServiceTest` y `FrozenReportTest`; [servicio](../evidence/2026-11-18-report-service.md) y [lector PostgreSQL](../evidence/2026-11-18-frozen-report.md). Las filas de préstamos de integración son datos de prueba insertados dentro de una transacción con rollback; esto verifica el reporte y no certifica el alta de préstamos por HTTP ni su adaptador de escritura pendiente.
