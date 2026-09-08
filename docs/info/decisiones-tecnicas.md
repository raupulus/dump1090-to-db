# Registro de Decisiones Técnicas

Registro cronológico (más reciente al final) de decisiones de diseño, arquitectura y deuda técnica conocida. No se reescribe el historial: si una decisión cambia, se añade una entrada nueva referenciando la anterior.

---

## 2026-07-03 — Creación de AGENTS.md y docs/info

**Decisión:** Se añade `AGENTS.md` en la raíz como resumen operativo para agentes de IA y colaboradores, y se crea `docs/info/` como lugar único donde documentar en Markdown la arquitectura en detalle y las decisiones técnicas del proyecto.

**Motivo:** El `README.md` original mezclaba guía de instalación con notas técnicas dispersas y contenía información desactualizada. Se separa documentación de uso (`README.md`) de documentación técnica/decisiones (`docs/info/`) y resumen para agentes (`AGENTS.md`).

---

## 2026-07-03 — Resolución de Deuda Técnica (fix1)

**Decisión:** Se corrigen múltiples problemas identificados en el análisis inicial del proyecto:
1. Se refactoriza `Models/Dbconnection.php` (`saveAirflight` y `deleteAirflight`) para que utilice consultas preparadas de PDO, eliminando el riesgo de inyección SQL.
2. Se corrige el bug en los reintentos de conexión de la BD (pasando de `usleep(300)` a `sleep(1)`).
3. Se añade captura de logs en caso de fallos de conexión a BD para evitar silenciamiento de errores.
4. Se mejora el uso de la API en `upload_data_to_api.php` añadiendo timeouts a cURL y evaluando códigos de estado HTTP (`200` o `201`) en lugar de validar un mensaje de éxito estricto en texto plano.
5. Se parametriza `start_dump1090_exporter.sh` introduciendo la lectura del `.env` y el uso de `$T_INTERVAL_CHECK` y `$T_INTERVAL_UPLOAD_API`.

---

## 2026-07-04 — Servicio Systemd

**Decisión:** Se sustituye el `@reboot` de crontab como método principal de arranque automático por un servicio systemd (`dump1090-to-db.service`), instalado mediante un nuevo script `install_service.sh`. El crontab se mantiene documentado en el README como alternativa "legacy" para sistemas sin systemd.

**Motivo:** El enfoque de crontab no reiniciaba el proceso si `start_dump1090_exporter.sh` moría (por ejemplo, si PHP lanzaba un fatal error no controlado), no ordenaba el arranque respecto a la red o PostgreSQL más allá de un `sleep 40` a ciegas, y mezclaba logs de stdout/stderr en un fichero plano sin rotación. Un servicio systemd resuelve las tres cosas: `Restart=on-failure`, `After=network-online.target postgresql.service`, y logging centralizado vía `journalctl`.

---

## 2026-09-08 — Migración a API V2 (Lotes y Telemetría de Hardware)

**Decisión:** Se actualiza el pipeline de subida a la API para alinearlo con el contrato **API V2 — AirFlight**.

1. **Endpoint por Lotes:** Se migra a `POST /api/v2/airflight/aircrafts/batch` enviando el cuerpo en formato JSON nativo (`Content-Type: application/json`) con autenticación Bearer Sanctum.
2. **Estructura del Payload:**
   - `hardware_device_id`: Entero identificativo del nodo receptor.
   - `data`: Array de aeronaves saneadas (hasta 500 por lote, configurable con `BATCH_SIZE`), asegurando tipos estrictos para `icao`, `flight`, `squawk`, `lat`, `lon`, `altitude`, `speed`, `track`, `messages`.
   - `hardware_device_info`: Bloque de estado de salud del hardware con `temp`, `voltage`, `cpu`, `disk`, `ram`, `uptime`, `ip_local` y métricas extendidas en `extra` (`throttled`, `undervoltage`, `load_1m`, `load_5m`, `load_15m`, `ram_free_mb`, `disk_free_gb`, `buffer_reports`).
3. **Nuevo Helper `Helpers/HardwareInfo.php`:** Clase dedicada a la recolección de métricas del sistema operativo directamente desde `/proc` y `/sys` para minimizar el coste de CPU y latencia (lecturas directas en memoria sin lanzar subprocesos pesados).
4. **Respuesta Envelope ApiResponseTrait:** Se evalúa tanto el código HTTP 201 como el flag `success === true` en la respuesta JSON para confirmar el borrado de los registros procesados en la base de datos local.
5. **Corrección de Tipo en `track` (`db.sql`):** Se detectó error 22P02 de PostgreSQL al insertar valores flotantes en una columna `INTEGER`. Se migró la columna `track` a `FLOAT` (`double precision`).

---

## 2026-09-08 — Reorganización de Directorios (`src/`, `scripts/`, `tests/`) y Protocolo de Documentación

**Decisión:** Se reestructura el repositorio para separar código fuente PHP (`src/`), scripts de automatización del sistema (`scripts/`), pruebas futuras (`tests/`) y se implanta el protocolo de documentación técnica estricto bajo `docs/`.

1. **Estructura de Código (`src/`):**
   - Se trasladan `Models/` y `Helpers/` a `src/Models/` y `src/Helpers/`.
   - Se trasladan los puntos de entrada PHP `dump1090_exporter.php` y `upload_data_to_api.php` a `src/`.
   - Se migra `composer.json` a un autoload PSR-4 estándar puro (`"App\\": "src/"`), eliminando el classmap.
2. **Estructura de Scripts (`scripts/`):**
   - Se agrupan todos los scripts bash (`start_dump1090_exporter.sh`, `install_service.sh`, `installer.sh`, `createdb.sh`, `optimize_pi.sh`, `update_and_optimize.sh`) en `scripts/`.
   - Cada script resuelve la raíz del proyecto dinámicamente mediante `PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"`.
   - Se actualiza la plantilla de systemd para apuntar a `scripts/start_dump1090_exporter.sh`.
3. **Protocolo `docs/`:**
   - Se configuran `docs/info/` (documentación viva), `docs/apis/` (documentación externa destilada), `docs/deploys/` (guías de despliegue) y `docs/future/` (ideas aplazadas).
   - Se añaden `/docs/planning/` y `/docs/auditorias/` a `.gitignore` como carpetas de trabajo efímero por desarrollador.

---
> Creado: 2026-07-03 · Última revisión: 2026-09-08
