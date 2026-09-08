# Catálogo de Comandos y Scripts

Guía operativa exhaustiva de todos los comandos, scripts ejecutables y servicios del proyecto `dump1090-to-db`.

---

## 1. Scripts de Ejecución Principal (`src/`)

### `src/dump1090_exporter.php`
Exportador individual. Lee el archivo volátil `aircraft.json`, normaliza las tramas ADS-B, persiste los registros en PostgreSQL y purga registros de más de 2 horas.
- **Invocación:**
  ```bash
  php src/dump1090_exporter.php
  ```
- **Variables de entorno:** `DEBUG`, `PATH_TO_AIRCRAFT_JSON`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- **Salida:** En modo `DEBUG=true` emite logs coloreados por stdout. En caso de fallo de conexión a BD devuelve código de salida `false` (0 en PHP).

### `src/upload_data_to_api.php`
Exportador hacia la API remota. Extrae un lote de registros pendientes en PostgreSQL, recopila la telemetría del sistema (`HardwareInfo`) y realiza una petición HTTP POST hacia el endpoint de la API V2. Si la API confirma `201 Created` y `success: true`, elimina los registros procesados.
- **Invocación:**
  ```bash
  php src/upload_data_to_api.php
  ```
- **Variables de entorno:** `DEBUG`, `API_URL`, `API_TOKEN`, `DEVICE_ID`, `BATCH_SIZE`, variables de conexión a DB.
- **Salida:** Muestra número de reportes procesados y el estado del resultado (`Resultado de subida API: ÉXITO` o `FALLO`).

---

## 2. Scripts de Control y Automatización (`scripts/`)

### `scripts/start_dump1090_exporter.sh`
Daemon supervisor en segundo plano. Bucle continuo que coordina la lectura de tramas y la subida periódica por lotes a la API.
- **Invocación:**
  ```bash
  ./scripts/start_dump1090_exporter.sh
  ```
- **Manejo de señales:** `trap` intercepta `SIGTERM` y `SIGINT` para apagado limpio inmediato (`exit 0`).
- **Tiempos y límites:**
  - Timeout de 20s para `dump1090_exporter.php`.
  - Timeout de 30s para `upload_data_to_api.php`.
  - Cada `$T_INTERVAL_UPLOAD_API` iteraciones (def: 3) lanza la subida.
  - Pausa de `$T_INTERVAL_CHECK` (def: 10s) entre iteraciones.

### `scripts/install_service.sh`
Aprovisiona y configura el servicio systemd en el sistema operativo anfitrión.
- **Invocación (requiere sudo):**
  ```bash
  sudo ./scripts/install_service.sh
  ```
- **Comportamiento:**
  - Detecta el usuario ejecutor vía `$SUDO_USER` (o `pi` por defecto).
  - Resuelve dependencias de Composer si falta `vendor/`.
  - Crea `.env` desde `.env.example` si no existe.
  - Compila `systemd/dump1090-to-db.service.template` reemplazando `__WORKING_DIR__` y `__SERVICE_USER__`.
  - Instala en `/etc/systemd/system/dump1090-to-db.service`, ejecuta `systemctl daemon-reload`, habilita (`enable`) y reinicia (`restart`) el servicio.

### `scripts/installer.sh`
Script de instalación completa de dependencias y base de datos para entornos locales o clonados.
- **Invocación:**
  ```bash
  ./scripts/installer.sh
  ```
- **Comportamiento:** Ejecuta `scripts/createdb.sh` y luego `composer install`.

### `scripts/createdb.sh`
Inicialización de la base de datos PostgreSQL.
- **Invocación:**
  ```bash
  ./scripts/createdb.sh
  ```
- **Comportamiento:** Ejecuta `sudo -u postgres psql` creando la BD `dump1090` con propietario `pi` e inyecta el esquema de tablas desde `db.sql`.

### `scripts/optimize_pi.sh`
Tuning para Raspberry Pi (optimización de I/O para evitar corrupción de MicroSD).
- **Invocación (requiere sudo):**
  ```bash
  sudo ./scripts/optimize_pi.sh
  ```
- **Acciones:**
  1. Desactiva y purga `dphys-swapfile`.
  2. Configura `/var/log` en RAM (`tmpfs`, 50m).
  3. Desactiva `fsync`, `synchronous_commit` y `full_page_writes` en `postgresql.conf`.
  4. Deshabilita servicios innecesarios (`bluetooth`, `cups`, `avahi-daemon`).
  5. Habilita corriente USB extendida (`max_usb_current=1`) para antenas RTL-SDR.

### `scripts/update_and_optimize.sh`
Actualización del sistema a Debian Trixie y purga de bloatware gráfico.
- **Invocación (requiere sudo):**
  ```bash
  sudo ./scripts/update_and_optimize.sh
  ```

---

## 3. Gestión del Servicio Systemd

| Acción | Comando |
|---|---|
| Estado del servicio | `systemctl status dump1090-to-db` |
| Ver logs en directo | `journalctl -u dump1090-to-db -f` |
| Ver últimas 50 líneas de log | `journalctl -u dump1090-to-db --no-pager -n 50` |
| Reiniciar servicio | `sudo systemctl restart dump1090-to-db` |
| Detener servicio | `sudo systemctl stop dump1090-to-db` |
| Deshabilitar arranque automático | `sudo systemctl disable dump1090-to-db` |

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
