# Arquitectura

## Flujo de datos

```
dump1090-fa (aircraft.json)
        │
        ▼
dump1090_exporter.php ──► Airflight (saneado/normalización) ──► Aircraft[] ──► Dbconnection::saveAirflight() ──► tabla `reports` (PostgreSQL)
        │
        ▼ (cada 3 iteraciones, vía start_dump1090_exporter.sh)
upload_data_to_api.php ──► Dbconnection::getLastsAirflight() ──► POST a API_URL ──► Dbconnection::deleteAirflight() (si la subida fue exitosa)
```

`start_dump1090_exporter.sh` es el proceso de entrada en producción: ejecuta `dump1090_exporter.php` en bucle (cada 10s) y, cada 3 iteraciones, ejecuta `upload_data_to_api.php` para vaciar la tabla `reports` hacia la API remota.

## Componentes

### `Models/Airflight.php`
Recibe el JSON decodificado de `aircraft.json` y, por cada aeronave, aplica saneados definidos en `$attributes` (conversión pies→metros, nudos→m/s, timestamps relativos a segundos antes de "ahora"). Produce instancias de `Aircraft`.

### `Models/Aircraft.php`
Objeto de valor plano con los campos ya normalizados que se persisten en la tabla `reports`.

### `Models/Dbconnection.php`
Envoltorio sobre `PDO`. Gestiona conexión (con reintentos), inserción (`saveAirflight`), lectura de últimos registros (`getLastsAirflight`) y borrado (`deleteAirflight`). Ver deuda técnica en [decisiones.md](decisiones.md).

### `Models/Api.php`
Clase stub sin implementación; la subida real a la API está resuelta directamente en `upload_data_to_api.php` mediante cURL, no a través de este modelo.

### `Helpers/Log.php`
Logger mínimo (`echo`) usado condicionalmente según la constante `DEBUG`.

## Esquema de base de datos

Tabla única `reports` (ver `db.sql`), sin claves foráneas ni tablas relacionadas. Cada fila es un "avistamiento" puntual de una aeronave, no un histórico de vuelo relacional.
