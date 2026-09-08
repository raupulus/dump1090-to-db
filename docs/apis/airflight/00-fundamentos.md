# API AirFlight V2 — 00 Fundamentos

Fundamentos de comunicación, autenticación y manejo de envelopes para la integración con la API AirFlight V2.

---

## 1. Autenticación y Autorización

La API está asegurada mediante **Laravel Sanctum**. Cada nodo emisor debe identificarse con un token personal portador (`Bearer Token`).

```http
Authorization: Bearer 47|FKv1XtcnjXvOuqp6Lg9YWfrPUoykYtk4R7Tz4pxU276ef48e
```

### Habilidades Requeridas (`Abilities`)
- `airflight:write`: Imprescindible para insertar registros en el pipeline de vuelos.
- `hardware:write`: Requerida si la petición adjunta el bloque `hardware_device_info`. Si el token carece de esta habilidad pero se envía telemetría, el backend rechaza la petición con código `403 Forbidden`.

---

## 2. Convenciones HTTP y Cabeceras

Todas las peticiones deben declarar explícitamente:
```http
Accept: application/json
Content-Type: application/json
```

---

## 3. Estructura de Respuestas (`ApiResponseTrait`)

La API encapsula todas las respuestas mediante un trait estándar con la siguiente estructura:

### Éxito (`201 Created` o `200 OK`)
```json
{
  "success": true,
  "status": 201,
  "message": "Batch de aeronaves procesado correctamente.",
  "data": {
    "processed": 100,
    "hardware_updated": true
  }
}
```

### Error de Validación (`422 Unprocessable Entity`)
```json
{
  "success": false,
  "status": 422,
  "message": "The given data was invalid.",
  "errors": {
    "data.0.icao": [
      "The data.0.icao field must be 6 characters."
    ]
  }
}
```

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
