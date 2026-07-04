# Export to db from dump1090

Lightweight PHP toolkit that reads the aircraft JSON feed produced by `dump1090-fa` (or any compatible ADS-B decoder), normalizes each detected aircraft record and stores it in a PostgreSQL database. Accumulated records can optionally be uploaded periodically to a remote API and purged from the local database afterwards.

Designed to run on low-power devices (e.g. Raspberry Pi) alongside an ADS-B receiver.

Repository: [https://gitlab.com/fryntiz/dump1090-to-db.git](https://gitlab.com/fryntiz/dump1090-to-db.git)

## Table of contents

- [Features](#features)
- [Requirements](#requirements)
- [Project structure](#project-structure)
- [Environment variables](#environment-variables)
- [Installation](#installation)
- [Manual start](#manual-start)
- [Automatic start](#automatic-start)
- [dump1090 JSON fields](#dump1090-json-fields)
- [Documentation](#documentation)
- [Author](#author)
- [License](#license)

## Features

- [x] Autoinstaller DB, create db and table
- [x] Airflight model
- [x] Get data from dump1090 json
- [x] Autoinstaller php dependencies
- [x] Vars from .env
- [x] Save data to postgresql DB
- [x] Api upload
- [x] Systemd service (autostart, auto-restart on failure)

## Requirements

- PHP >= 8.0 (`ext-pdo`, `ext-curl`)
- PostgreSQL
- Composer >= 2.0.11

## Project structure

```
.
├── dump1090_exporter.php      # Entry point: reads aircraft.json and stores rows in DB
├── upload_data_to_api.php     # Entry point: uploads pending rows to the API and purges them
├── start_dump1090_exporter.sh # Continuous loop driving both scripts (service entry point)
├── installer.sh / createdb.sh # DB + dependency provisioning
├── install_service.sh         # Installs and starts the systemd service
├── systemd/                   # systemd unit template
├── db.sql                     # Schema for the `reports` table
├── Models/                    # Airflight, Aircraft, Dbconnection, Api
├── Helpers/                   # Log helper
└── docs/info/                 # Extended technical documentation & decision log
```

See [AGENTS.md](AGENTS.md) for a component-by-component breakdown aimed at contributors and AI coding agents.

## Environment variables

| Variable | Description | Default |
|---|---|---|
| `PATH_TO_AIRCRAFT_JSON` | Path to the dump1090 `aircraft.json` file | `/run/dump1090-fa/aircraft.json` |
| `DB_CONNECTION` | PDO driver name | `pgsql` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `5432` |
| `DB_DATABASE` | Database name | `dump1090` |
| `DB_USERNAME` | Database user | `dbuser` |
| `DB_PASSWORD` | Database password | — |
| `API_URL` | Endpoint the accumulated reports are POSTed to | — |
| `API_TOKEN` | Bearer token sent to the API | — |
| `DEVICE_ID` | Hardware/device identifier sent with each upload | — |
| `DEBUG` | Enables verbose logging | `false` |

See `.env.example` for a ready-to-copy template (`cp .env.example .env`).

> **Note:** the upload cadence (one upload every 3 exporter iterations) and the polling interval (`sleep 10`) are currently hardcoded in `start_dump1090_exporter.sh`, not read from environment variables. See [docs/info/decisiones.md](docs/info/decisiones.md) for details.

## Installation

First, set the environment variables and install the software dependencies (sections above).

Next, run **installer.sh**. This script creates the database, tables, and resolves composer dependencies.

```bash
./installer.sh
```

## Manual start

Preferred method, via script:

```bash
./start_dump1090_exporter.sh
```

Manual PHP script execution:

```bash
php dump1090_exporter.php
```

## Automatic start

### systemd service (recommended)

`install_service.sh` sets up and starts `dump1090-to-db` as a systemd service: resolves the base dependencies (`installer.sh`) if missing, creates `.env` from `.env.example` if missing, installs the unit file, and enables + starts the service.

```bash
sudo ./install_service.sh
```

Common operations afterwards:

```bash
systemctl status dump1090-to-db      # check status
journalctl -u dump1090-to-db -f      # follow logs
sudo systemctl restart dump1090-to-db
sudo systemctl stop dump1090-to-db
sudo systemctl disable dump1090-to-db
```

The service runs `start_dump1090_exporter.sh` in a loop (`Type=simple`), restarts automatically on failure (`Restart=on-failure`), and waits for the network to be up before starting (`After=network-online.target`). See the unit template at [systemd/dump1090-to-db.service.template](systemd/dump1090-to-db.service.template) and the rationale in [docs/info/decisiones.md](docs/info/decisiones.md).

### Cron job (legacy alternative)

On systems without systemd, or if you prefer not to install a system service, you can still run it via `@reboot` in crontab:

```bash
sudo nano /etc/crontab
```

Adjust the path and add the following line:

```
@reboot pi sleep 40 && cd /home/pi/git/dump1090-to-db && . /start_dump1090_exporter.sh >> /tmp/dump1090.log 2>> /tmp/dump1090.log
```

Unlike the systemd service, this approach does not restart the process automatically if it crashes.

## dump1090 JSON fields

- `hex` → ICAO 24 bits (6 dígitos hexadecimales)
- `squawk` → Código de transpondedor seleccionado (Señal squawk en representación octal)
- `flight` → Nombre del vuelo
- `lat`, `lon` → latitud y longitud con decimales
- `nucp` → the NUCp (navigational uncertainty category) reported for the position
- `seen_pos` → Tiempo en segundos (antes de ahora) desde el que fue visto por última vez
- `altitude` → Altitud en pies, o "ground" si está en tierra
- `vert_rate` → Velocidad vertical en pies/minuto
- `track` → track verdadero sobre el suelo en grados (0-359)
- `speed` → velocidad informada en kt. esto suele ser la velocidad sobre el suelo
- `messages` → número total de mensajes de modo s recibidos desde esta aeronave
- `seen` → cuánto tiempo (en segundos antes de "ahora") se recibió un mensaje de este avión por última vez
- `rssi` → rssi promedio reciente (potencia de señal), en dbfs; esto siempre será negativo.

## Documentation

Extended technical documentation, architecture notes and the decision log are kept in **[docs/info/](docs/info/)** as Markdown. [AGENTS.md](AGENTS.md) contains an operational summary of the codebase aimed at AI coding agents and contributors.

## Author

- Name: Raúl Caro Pastorino
- Web: [fryntiz.es](https://fryntiz.es)
- Twitter: [@fryntiz](https://twitter.com/fryntiz)

## License

[GNU General Public License v3.0](LICENSE)
