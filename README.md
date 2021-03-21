# Export to db from dump1090

Store in database dump1090 captures.

Repository [https://gitlab.com/fryntiz/dump1090-to-db.git](https://gitlab.com/fryntiz/dump1090-to-db.git)

## Author

- Name: Raúl Caro Pastorino
- Web: [fryntiz.es](https://fryntiz.es)
- Twitter: [@fryntiz](https://twitter.com/fryntiz)

## Features

- [x] Autoinstaller DB, create db and table
- [x] Airflight model
- [x] Get data from dump1090 json
- [x] Autoinstaller php dependencies 
- [x] Vars from .env
- [x] Save data to postgresql DB
- [ ] Api upload
- [ ] Create Daemon Service

## Dependencias

- php >= 8.0
- postgresql
- composer >= 2.0.11

## Datos del json

- hex → ICAO 24 bits (6 dígitos hexadecimales)
- squawk → Código de transpondedor seleccionado (Señal squawk en representación octal)
- flight → Nombre del vuelo
- lat, lon → latitud y longitud con decimales
- nucp → the NUCp (navigational uncertainty category) reported for the position
- seen_pos → Tiempo en segundos (antes de ahora) desde el que fue visto por última vez
- altitude → Altitud en pies, o "ground" si está en tierra
- vert_rate → Velocidad vertical en pies/minuto
- track: track verdadero sobre el suelo en grados (0-359)
- speed: velocidad informada en kt. esto suele ser la velocidad sobre el suelo, pero podría ser ias; no se puede notar la diferencia aquí, ¡lo siento!
- messages: número total de mensajes de modo s recibidos desde esta aeronave
- seen: cuánto tiempo (en segundos antes de "ahora") se recibió un mensaje de este avión por última vez
- rssi: rssi promedio reciente (potencia de señal), en dbfs; esto siempre será negativo.


## Environment Variables 

- DB_CONNECTION → Type of SGBD (default psql)
- DB_HOST → IP to database HOST
- DB_PORT → DB port
- DB_DATABASE → DB Name of database
- DB_USERNAME → DB Username
- DB_PASSWORD → DB Password
- API_URL → Api Endpoint
- API_TOKEN → Api Token
- DEBUG → Debug Enabled
- T_INTERVAL_CHECK → Interval in seconds between checks for new records
- T_INTERVAL_UPLOAD_API → Interval in seconds between uploads to the api

## Installation

First, you need set environment vars and software dependencies (sections above).

Next step is execute script **installer.sh**.
This script create db, tables and resolve composer dependencies.

```bash
./installer.sh
```

## Manual Start

Method prefer from script:

```bash
./start_dump1090_exporter.sh
```

Manual php script execution:

```bash
php dump1090_exporter.php
```
