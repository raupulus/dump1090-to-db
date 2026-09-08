# Dominio: Subida de Lotes y Telemetría (`POST /api/v2/airflight/aircrafts/batch`)

Especificación funcional y técnica del endpoint para la sincronización masiva de aeronaves y reporte del estado del dispositivo.

---

## 1. Definición del Endpoint

- **Método:** `POST`
- **Ruta:** `/api/v2/airflight/aircrafts/batch`
- **Autenticación:** `Bearer Token` (Sanctum) con `airflight:write` (y `hardware:write` si se incluye telemetría).

---

## 2. Estructura Completa del Payload

```json
{
  "hardware_device_id": 15,
  "hardware_device_info": {
    "hardware_device_id": 15,
    "temp": 52.5,
    "voltage": 1.313,
    "cpu": 21.9,
    "disk": 35.9,
    "ram": 28.9,
    "battery_level": null,
    "uptime": 25455,
    "ip_local": "172.18.1.58",
    "extra": {
      "buffer_reports": 0,
      "throttled": "0x50005",
      "undervoltage_detected": true,
      "load_1m": 0.55,
      "load_5m": 0.42,
      "ram_free_mb": 673,
      "disk_free_gb": 18.2
    }
  },
  "data": [
    {
      "icao": "346353",
      "flight": "IBE3102",
      "squawk": "1000",
      "lat": 36.96513,
      "lon": -6.141596,
      "altitude": 10275.0,
      "speed": 235.0,
      "track": 45,
      "vert_rate": 0.0,
      "messages": 219,
      "rssi": -24.7,
      "emergency": null,
      "seen": null,
      "seen_pos": null
    }
  ]
}
```

---

## 3. Comportamiento en el Servidor

1. Valida el token y las habilidades requeridas.
2. Abre una transacción de base de datos.
3. Procesa cada elemento de `data`: busca o crea la aeronave por `icao` y añade el punto de seguimiento en el histórico.
4. Si se proporciona `hardware_device_info`, actualiza el modelo `HardwareDevice` correspondiente con las métricas y fecha de contacto.
5. Confirma la transacción y devuelve `HTTP 201 Created` con el número total de reportes procesados.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
