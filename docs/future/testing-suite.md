# Futuro: Suite de Pruebas Automatizadas con PHPUnit

Planificación para la incorporación de testing automatizado en el proyecto `dump1090-to-db`.

---

## 1. Contexto y Estado Actual
Actualmente el proyecto no cuenta con suite de tests automatizados configurada. Las validaciones se realizan en integración directa en hardware físico (Raspberry Pi 2 con antena RTL-SDR y PostgreSQL). Se ha creado el directorio reservado `tests/` para alojar los futuros tests.

---

## 2. Alcance Propuesto

1. **Tests Unitarios:**
   - `AirflightTest`: Verificación de las transformaciones matemáticas en `feetToMeters`, `knotsToMeters` y cálculo de timestamps relativos con datasets reales de `aircraft.json`.
   - `HardwareInfoTest`: Comprobación del parsing de pseudo-ficheros `/proc/meminfo`, `/proc/uptime` y `/sys/.../temp` mediante fixtures simuladas.
2. **Tests de Integración:**
   - `DbconnectionTest`: Operaciones CRUD sobre una base de datos SQLite en memoria (`sqlite::memory:`) para validar inserción, purga y lectura por lotes.
   - `UploadApiTest`: Mock de peticiones HTTP cURL o servidor mock HTTP local para comprobar respuestas `201`, redirecciones y errores `422`.

---

## 3. Dependencias Requeridas
- `phpunit/phpunit` (como dependencia de desarrollo `require-dev` en `composer.json`).
- Configuración de `phpunit.xml`.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
