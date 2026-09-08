# Integración: API AirFlight V2

Documentación técnica interna sobre cómo integra `dump1090-to-db` el servicio remoto de seguimiento de aeronaves (AirFlight API V2).

Para consultar la especificación oficial destilada y validada de la API, ver [`docs/apis/airflight/`](../../apis/airflight/README.md).

---

## 1. Qué hace y qué NO hace nuestra integración

### Qué hace
- Extrae de la base de datos local un lote de avistamientos ADS-B pendientes (hasta `BATCH_SIZE`, por defecto 100).
- Normaliza y sanea las propiedades de cada aeronave para cumplir estrictamente la validación del endpoint `POST /api/v2/airflight/aircrafts/batch`:
  - `icao`: Cadena hex en minúsculas de 6 caracteres.
  - `flight`: Callsign limpio sin espacios laterales.
  - `squawk`, `emergency`: Cadenas de texto alfanumérico o null.
  - `lat`, `lon`: Flotantes WGS84 dentro de rangos válidos.
  - `altitude`, `speed`, `track`, `vert_rate`, `rssi`, `messages`: Valores numéricos saneados.
- Adjunta el bloque de telemetría de salud `hardware_device_info` generado por `App\Helpers\HardwareInfo`.
- Envía la petición HTTP POST codificada en JSON nativo mediante cURL, siguiendo redirecciones (`CURLOPT_FOLLOWLOCATION`) y preservando el método (`CURLOPT_POSTREDIR = CURL_REDIR_POST_ALL`).
- Valida la respuesta contra el envelope estándar (`status: 201` y `success: true`).
- Si la subida es confirmada, elimina de PostgreSQL los registros procesados.

### Qué NO hace
- No reintenta indefinidamente peticiones fallidas en el mismo ciclo; ante fallo cURL o respuesta HTTP distinta de 201, conserva los registros en BD para el siguiente ciclo.
- No envía avistamientos simulados o sintéticos en producción.

---

## 2. Especificación de Campos y Unidades de Medida

### Datos de Vuelos (`data.*`)

| Campo | Tipo | Unidad de Medida | Rango / Formato | Ejemplo | Descripción |
|---|---|---|---|---|---|
| `icao` | string | — | 6 caracteres hexadecimales | `"4ca61f"` | **(Requerido)** Identificador único ICAO de 24 bits. |
| `flight` | string \| null | — | Callsign sin espacios laterales | `"RYR11CL"` | Indicativo de llamada o número de vuelo. |
| `squawk` | string \| null | — | 4 dígitos octales | `"7105"` | Código asignado en el transpondedor del avión. |
| `lat` | float \| null | Grados decimales WGS84 (`°`) | -90.0 a 90.0 | `36.623623` | Latitud. `null` si la emisión Mode-S no trae GPS. |
| `lon` | float \| null | Grados decimales WGS84 (`°`) | -180.0 a 180.0 | `-5.885049` | Longitud. `null` si no hay posición fijada. |
| `altitude` | float \| null | **Metros (`m`)** | 0.0 a 60000.0 | `11277.0` | Altitud barométrica/geométrica sobre nivel del mar (`ft / 3.281`). |
| `speed` | float \| null | **Metros por segundo (`m/s`)** | 0.0 a 1000.0 | `232.2` | Velocidad horizontal sobre tierra (`kt / 1.94384`). |
| `track` | int \| null | **Grados sexagesimales (`°`)** | 0 a 360 | `214` | Rumbo respecto al norte verdadero. |
| `vert_rate` | float \| null | **Metros por segundo (`m/s`)** | -100.0 a 100.0 | `-21.1` | Tasa vertical (`(fpm / 3.281) / 60`). Positivo=ascenso, Negativo=descenso, 0.0=nivelado. |
| `messages` | int \| null | Conteo entero de tramas | >= 0 | `86` | Total acumulado de tramas recibidas de este aparato. |
| `rssi` | float \| null | **dBFS (decibelios fondo escala)** | -100.0 a 0.0 | `-24.7` | Potencia de señal RF recibida por el RTL-SDR (siempre negativo). |
| `emergency` | string \| null | Cadena de estado | none, general, lifeguard... | `"general"` | Estado de emergencia declarado en transpondedor (`null` si no aplica). |
| `seen` | null | — | `null` | Reservado por contrato API V2. |
| `seen_pos` | null | — | `null` | Reservado por contrato API V2. |

### Telemetría de Hardware (`hardware_device_info`)

| Campo | Tipo | Unidad de Medida | Ejemplo | Descripción |
|---|---|---|---|---|
| `hardware_device_id` | integer | — | `15` | ID del dispositivo receptor registrado en la API. |
| `temp` | float | **Grados Celsius (`°C`)** | `43.5` | Temperatura térmica del SoC de la Raspberry Pi. |
| `voltage` | float | **Voltios (`V`)** | `0.85` | Voltaje del núcleo medido por hardware. |
| `cpu` | float | **Porcentaje (`%`)** | `12.5` | Porcentaje de carga estimada de la CPU. |
| `disk` | float | **Porcentaje (`%`)** | `28.0` | Porcentaje de ocupación de la partición raíz `/`. |
| `ram` | float | **Porcentaje (`%`)** | `45.2` | Porcentaje de memoria RAM en uso. |
| `uptime` | integer | **Segundos (`s`)** | `172800` | Tiempo de actividad ininterrumpido del sistema. |
| `ip_local` | string | Dirección IPv4 | `"172.18.1.58"` | Dirección IP privada del nodo en red local. |
| `extra.throttled` | string \| bool | Estado throttling | `"0x0"` | Bandera de bajo voltaje o throttling de silicio. |
| `extra.undervoltage` | boolean | Booleano | `false` | Indica si hubo caídas de tensión por fuente deficiente. |
| `extra.load_1m` | float | Media de carga | `0.42` | Carga media del kernel en el último minuto. |
| `extra.load_5m` | float | Media de carga | `0.38` | Carga media del kernel en los últimos 5 minutos. |
| `extra.load_15m` | float | Media de carga | `0.30` | Carga media del kernel en los últimos 15 minutos. |
| `extra.ram_free_mb` | float | **Megabytes (`MB`)** | `240.5` | Memoria RAM libre disponible. |
| `extra.disk_free_gb` | float | **Gigabytes (`GB`)** | `18.2` | Espacio libre en disco raíz disponible. |
| `extra.buffer_reports` | integer | Conteo entero | `0` | Reportes pendientes en la base de datos local en RAM. |

---

## 3. Flujo de Comunicación

```text
PostgreSQL (Local) 
       │ (SELECT LIMIT 100)
       ▼
upload_data_to_api.php ◄─── HardwareInfo::getPayload()
       │
       ▼ (JSON POST + Bearer Sanctum)
cURL ──────────────────────► https://api.raupulus.dev/api/v2/airflight/aircrafts/batch
                                    │
                                    ▼ (HTTP 201 {"success": true})
upload_data_to_api.php ◄────────────┘
       │
       ▼ (DELETE WHERE id IN (...))
PostgreSQL (Local)
```

---

## 4. Puntos de Entrada y Consumo
- **Script ejecutador:**
  - `src/upload_data_to_api.php`: Invocado periódicamente por el daemon `scripts/start_dump1090_exporter.sh`.
- **Ruta de destino:**
  - `POST /api/v2/airflight/aircrafts/batch`

---

## 5. Dependencias
- **Depende de:**
  - `ext-curl`: Extensión nativa de PHP para peticiones HTTP/REST.
  - `App\Helpers\HardwareInfo`: Generador del bloque de hardware.
  - `App\Models\Dbconnection`: Extracción y borrado en base de datos.
- **Enlace a documentación de API:**
  - Contrato oficial: [`docs/apis/airflight/README.md`](../../apis/airflight/README.md).
  - Fundamentos: [`docs/apis/airflight/00-fundamentos.md`](../../apis/airflight/00-fundamentos.md).

---

## 6. Configuración Requerida
| Variable | Tipo | Valor por defecto | Efecto / Comportamiento |
|---|---|---|---|
| `API_URL` | `string` | `https://api.raupulus.dev/api/v2/airflight/aircrafts/batch` | URL canónica completa del endpoint batch. |
| `API_TOKEN` | `string` | (vacío) | Token de acceso Bearer (Laravel Sanctum). |
| `DEVICE_ID` | `int` | `null` | Identificador del dispositivo en el backend. |
| `BATCH_SIZE` | `int` | `100` | Número de aeronaves por petición (máx 500). |

---

## 7. Trampas y Consideraciones Técnicas
- **Redirección 301 de dominio legado:** El dominio `api.fryntiz.dev` redirige con 301 a `https://api.raupulus.dev`. Si cURL no tiene `CURLOPT_POSTREDIR = CURL_REDIR_POST_ALL`, cURL transforma la petición en `GET` tras el 301 y descarta el cuerpo POST. Por ello se fija la URL canónica directa en `.env`.
- **Envelope ApiResponseTrait:** La API no devuelve los datos planos; envuelve la respuesta en `{"success": true, "data": {...}}`. El script comprueba explícitamente `!empty($json['success'])`.

---

## 8. Tests que lo cubren
- `⚠️ sin verificar` (No existe suite de tests con mocks HTTP).

---

## 9. Pendiente real
- [ ] Implementar un test con mock de cURL o servidor HTTP local de pruebas para validar control de errores 422/500 y timeouts.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
