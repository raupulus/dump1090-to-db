# Contrato API V2 — Seguimiento de vuelos (AirFlight) [Fuente Original]

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.

---

## 1. Autenticación

Todas las rutas requieren cabecera HTTP:

```http
Authorization: Bearer <token-sanctum>
```

Habilidades (`abilities`) mínimas del token:

| Endpoint | Habilidad requerida | Notas |
|---|---|---|
| `POST /api/v2/airflight/aircrafts/batch` | `airflight:write` | Permite subir lotes de aeronaves |
| Telemetría opcional en batch | `hardware:write` | Requerido solo si se incluye `hardware_device_info` |

---

## 2. Endpoints

### `POST /api/v2/airflight/aircrafts/batch`

Crea o actualiza múltiples avistamientos de aeronaves en una única transacción atómica y actualiza la telemetría del dispositivo si se incluye el bloque correspondiente.

#### Cabeceras
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>
```

#### Parámetros del Body (JSON)

| Campo | Tipo | Requerido | Reglas |
|---|---|---|---|
| `hardware_device_id` | integer | No | ID del dispositivo emisor registrado en el sistema. |
| `data` | array | **Sí** | Array de aeronaves. Mínimo 1, máximo 500 elementos. |
| `data.*.icao` | string | **Sí** | 6 caracteres hexadecimales (código ICAO de la aeronave). |
| `data.*.flight` | string | No | Callsign o número de vuelo (máx. 10 chars). |
| `data.*.squawk` | string | No | Código transpondedor (4 dígitos octales). |
| `data.*.lat` | numeric | No | Latitud (-90 a 90). |
| `data.*.lon` | numeric | No | Longitud (-180 a 180). |
| `data.*.altitude` | numeric | No | Altitud barométrica o geométrica en metros. |
| `data.*.speed` | numeric | No | Velocidad horizontal en metros por segundo. |
| `data.*.track` | numeric | No | Rumbo en grados (0 a 360). Admite flotantes. |
| `data.*.vert_rate` | numeric | No | Tasa vertical de ascenso/descenso en m/s. |
| `data.*.rssi` | numeric | No | Potencia de señal en dBFS (-100 a 0). |
| `data.*.emergency` | string | No | Estado de emergencia declarado o null. |
| `data.*.seen` | numeric | No | Segundos desde el último mensaje recibido (null). |
| `data.*.seen_pos` | numeric | No | Segundos desde la última posición recibida (null). |
| `data.*.messages` | integer | No | Total de mensajes recibidos de esta aeronave. |
| `hardware_device_info` | object | No | Objeto de telemetría del dispositivo. |
| `hardware_device_info.hardware_device_id` | integer | **Sí** (si se envía el bloque) | ID del dispositivo receptor. |
| `hardware_device_info.temp` | numeric | No | Temperatura en °C (-50 a 150). |
| `hardware_device_info.voltage` | numeric | No | Voltaje en Voltios (0 a 50). |
| `hardware_device_info.cpu` | numeric | No | Porcentaje de CPU (0 a 100). |
| `hardware_device_info.disk` | numeric | No | Porcentaje de disco (0 a 100). |
| `hardware_device_info.ram` | numeric | No | Porcentaje de RAM (0 a 100). |
| `hardware_device_info.battery_level` | integer | No | Porcentaje de batería (0 a 100) o null. |
| `hardware_device_info.uptime` | integer | No | Segundos de actividad (> 0). |
| `hardware_device_info.ip_local` | string | No | Dirección IP privada. |
| `hardware_device_info.extra` | array | No | Hasta 30 pares clave/valor escalar (máx 255 caracteres por valor). |

#### Respuesta de Éxito (`201 Created`)
```json
{
  "success": true,
  "status": 201,
  "message": "Batch de aeronaves procesado correctamente.",
  "data": {
    "processed": 94,
    "hardware_updated": true
  }
}
```

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
