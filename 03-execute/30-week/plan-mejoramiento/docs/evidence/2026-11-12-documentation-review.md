# Revisión documental del 12 de noviembre

Tipo de evidencia: preparación documental, **no ejecución del producto**. Hora de comprobación: 19:25 America/Bogota. El código de aplicación y las pruebas automatizadas siguen pendientes.

## Resultados observados

| Comprobación | Resultado |
|---|---|
| Primera línea del README | Coincide exactamente con la frase del acta |
| Subcarpetas | docs, api, app, page, infra y tool presentes |
| Historias | 10 |
| Reglas de negocio | 6 |
| Requisitos no funcionales | 5 |
| Tareas | 23, todas pendientes de evidencia de producto |
| Operaciones HTTP previstas | 21 |
| ADR | 6 |
| Grafo de dependencias de tareas | Sin ciclos ni referencias a tareas inexistentes |
| Enlaces relativos Markdown | Sin destinos ausentes en los 26 documentos de la revisión inicial |
| Bloques de código Markdown | Delimitadores emparejados |
| PDF adjunto | 6 páginas, copia idéntica al original aportado |
| `.gitignore` | Excluye .env, node_modules y vendor en esta entrega |

SHA-256 del PDF original y de la copia: `b0dc44fb3eb1d0ac9d0997c05673520b369f1a2a1bc4bd51380679d6e66fa819`.

Se revisaron visualmente las seis páginas del PDF. Los datos del aprendiz aparecen como A03; no se alteraron los nombres o firmas de la instructora del documento original.

## Límites

- La comprobación estructural no demuestra que el contrato o las reglas estén implementados.
- No se ejecutaron PHPUnit, React, concurrencia, carga ni arranque de contenedores porque el producto aún no existe.
- No hay ocho días de commits cumplidos. Este es el primer día previsto de avance propio.
- El análisis del proyecto formativo usó un informe histórico; su versión vigente todavía debe revisarse.

Para repetir la revisión: contar encabezados HU en `spec.md`, filas RN/RNF, filas T en `tasks.md`, rutas numeradas en `api-contract.md`; comprobar enlaces relativos desde el directorio de cada documento, comparar el hash del PDF y revisar los patrones ignorados con `git check-ignore`. Para la publicación revisar además `git diff --cached --check`, los archivos del índice y el SHA del fork después del push.
