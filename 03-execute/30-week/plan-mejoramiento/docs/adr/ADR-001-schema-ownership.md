# ADR-001: propiedad del esquema

- Estado: propuesta, 12 de noviembre de 2026.
- Contexto: el examen anterior recibió descuentos por tablas y columnas heredadas del esqueleto del framework. Mantener SQL de arranque en `infra` y migraciones en `api` duplicaría la fuente del esquema.
- Decisión: todas las entidades, índices, restricciones y categorías pertenecen a las migraciones Laravel. Solo seis tablas de negocio en `lab_equipment`; registro técnico de migraciones fuera de ese esquema. UUID, valores y tiempos de negocio se asignan explícitamente desde la aplicación.
- Alternativas: DDL en Compose (duplica responsabilidades); conservar migraciones de ejemplo (agrega funcionalidades no solicitadas); crear tablas automáticamente desde modelos (pierde evolución controlada).
- Consecuencias: retirar tablas de ejemplo antes del primer migrate; evitar timestamps y campos de autenticación ajenos al contrato. El arranque vacío primero migra y luego aprovisiona admin desde entorno.
- Evidencia requerida: T-02 y T-18, inspección de esquema e inserciones inválidas. Un `docker compose` aislado no basta para declarar las migraciones correctas.
