# B14 · Reloj aislado

El 19 de noviembre se implementó DemoFileClock según ADR-006. Solo se compone con APP_ENV=testing y DEMO_CLOCK_FILE explícito. La configuración local/producción se rechaza al registrar el proveedor. Un archivo inválido o ausente falla; el valor system usa tiempo real UTC. La instancia normal mantiene SystemClock. No hay nuevas operaciones HTTP ni cambios al esquema.

Después de reconstruir Compose, composer test aprobó 331 pruebas y 1.690 aserciones. DemoFileClockTest verifica lectura actualizada, restauración, calendario inválido, archivo ausente y rechazo local/producción. Commit: git log --grep="feat(time): isolate demo clock behind testing configuration".

T-21 sigue pendiente del sembrador completo y RNF-1 de la carga/medición por API.
