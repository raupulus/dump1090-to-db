# Registro de decisiones técnicas

Registro cronológico (más reciente al final) de decisiones de diseño y deuda técnica conocida. No se reescribe el historial: si una decisión cambia, se añade una entrada nueva referenciando la anterior.

---

## 2026-07-03 — Creación de AGENTS.md y docs/info

**Decisión:** se añade `AGENTS.md` en la raíz como resumen operativo para agentes de IA y colaboradores, y se crea `docs/info/` como lugar único donde documentar en Markdown la arquitectura en detalle y las decisiones técnicas del proyecto.

**Motivo:** el README.md original mezclaba guía de instalación con notas técnicas dispersas y contenía información desactualizada. Se separa documentación de uso (README) de documentación técnica/decisiones (docs/info) y resumen para agentes (AGENTS.md).

### Deuda técnica identificada durante este análisis

- **Riesgo de inyección SQL en `Models/Dbconnection.php`:** los métodos `saveAirflight`, `getLastsAirflight` y `deleteAirflight` construyen las queries interpolando valores directamente en el string SQL, en vez de usar parámetros ligados de PDO (`prepare`/`bindValue`). Actualmente los valores provienen del JSON de `dump1090-fa` (fuente semi-confiable, local), por lo que el riesgo práctico es bajo, pero cualquier cambio que permita datos externos no controlados en este flujo requeriría migrar a *prepared statements* con parámetros.
- **Variables de entorno documentadas pero no usadas:** el README anterior mencionaba `T_INTERVAL_CHECK` y `T_INTERVAL_UPLOAD_API` como variables de entorno, pero no existen referencias a ellas en el código. El intervalo real (`sleep 10`) y la cadencia de subida (cada 3 iteraciones) están hardcodeados en `start_dump1090_exporter.sh`. Se ha corregido el README para reflejar el comportamiento real; si se desea hacerlos configurables, es un trabajo pendiente.
- **`Models/Api.php` es un stub vacío:** no se usa; la lógica de subida a la API está implementada directamente en `upload_data_to_api.php` con cURL.
- **Ejecución como daemon/servicio pendiente:** listado como feature no completada en el README desde el origen del proyecto; actualmente el "daemon" es un script en bucle (`start_dump1090_exporter.sh`) lanzado vía `@reboot` en crontab, sin gestión de reinicio ante fallos (systemd, supervisor, etc.).

---

## 2026-07-03 — Resolución de Deuda Técnica (fix1)

**Decisión:** se corrigen múltiples problemas identificados en el análisis del proyecto.
1. Se refactoriza `Models/Dbconnection.php` (`saveAirflight` y `deleteAirflight`) para que utilice consultas preparadas de PDO, eliminando el riesgo teórico de inyección SQL.
2. Se corrige el bug en los reintentos de conexión de la BD (pasando de `usleep(300)` a `sleep(1)`).
3. Se añade captura de logs en caso de fallos de conexión a BD para evitar problemas silenciosos.
4. Se mejora el uso de la API en `upload_data_to_api.php` añadiendo timeouts a cURL y evaluando el código de estado HTTP (`200` o `201`) en lugar de validar un mensaje de éxito estricto en texto plano.
5. Se parametriza `start_dump1090_exporter.sh` introduciendo la lectura del `.env` y el uso de `$T_INTERVAL_CHECK` y `$T_INTERVAL_UPLOAD_API`.

---

## 2026-07-04 — Servicio systemd

**Decisión:** se sustituye el `@reboot` de crontab como método principal de arranque automático por un servicio systemd (`dump1090-to-db.service`), instalado mediante un nuevo script `install_service.sh`. El crontab se mantiene documentado en el README como alternativa "legacy" para sistemas sin systemd.

**Motivo:** el enfoque de crontab no reinicia el proceso si `start_dump1090_exporter.sh` muere (por ejemplo, si `php` lanza un fatal error no controlado), no ordena el arranque respecto a la red o PostgreSQL más allá de un `sleep 40` a ciegas, y mezcla logs de stdout/stderr en un fichero plano (`/tmp/dump1090.log`) sin rotación. Un servicio systemd resuelve las tres cosas: `Restart=on-failure`, `After=network-online.target postgresql.service`, y logging centralizado vía `journalctl`.

**Implementación:**
- `systemd/dump1090-to-db.service.template`: unit con placeholders `__WORKING_DIR__` y `__SERVICE_USER__`, `Type=simple` sobre `start_dump1090_exporter.sh` (que ya corre en bucle infinito, sin necesidad de `Type=forking`).
- `install_service.sh`: debe ejecutarse con `sudo`. Resuelve dependencias base (`installer.sh`) y `.env` si faltan, sustituye los placeholders de la plantilla con `sed`, copia la unit a `/etc/systemd/system/`, y ejecuta `daemon-reload` + `enable` + `restart`. Es idempotente: puede volver a ejecutarse tras cambios (por ejemplo, si el proyecto se mueve de ruta) para regenerar la unit.
- El usuario del servicio se toma de `$SUDO_USER` (quien invocó `sudo`), con `pi` como valor por defecto si no se puede detectar.

**Pendiente/a vigilar:** el servicio usa `Type=simple` sobre un bucle bash sin manejo explícito de `SIGTERM`; ver nota en [AGENTS.md](../../AGENTS.md#puntos-de-atención-conocidos). El `StartLimitBurst=5` / `StartLimitIntervalSec=60` de la unit evita reinicios en bucle infinito si el fallo es persistente (por ejemplo, credenciales de BD inválidas); en ese caso systemd marcará el servicio como fallido tras 5 reinicios en 60s y habrá que revisar `journalctl -u dump1090-to-db` antes de reintentar.
