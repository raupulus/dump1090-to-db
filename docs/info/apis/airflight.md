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
  - `squawk`: Cadena de 4 dígitos o null.
  - `lat`, `lon`: Flotantes WGS84 dentro de rangos válidos.
  - `altitude`, `speed`, `track`, `messages`: Valores numéricos saneados.
- Adjunta el bloque de telemetría de salud `hardware_device_info` generado por `App\Helpers\HardwareInfo`.
- Envía la petición HTTP POST codificada en JSON nativo mediante cURL, siguiendo redirecciones (`CURLOPT_FOLLOWLOCATION`) y preservando el método (`CURLOPT_POSTREDIR = CURL_REDIR_POST_ALL`).
- Valida la respuesta contra el envelope estándar (`status: 201` y `success: true`).
- Si la subida es confirmada, elimina de PostgreSQL los registros procesados.

### Qué NO hace
- No reintenta indefinidamente peticiones fallidas en el mismo ciclo; ante fallo cURL o respuesta HTTP distinta de 201, conserva los registros en BD para el siguiente ciclo.
- No envía avistamientos simulados o sintéticos en producción.

---

## 2. Flujo de Comunicación

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

## 3. Puntos de Entrada y Consumo
- **Script ejecutador:**
  - `src/upload_data_to_api.php`: Invocado periódicamente por el daemon `scripts/start_dump1090_exporter.sh`.
- **Ruta de destino:**
  - `POST /api/v2/airflight/aircrafts/batch`

---

## 4. Dependencias
- **Depende de:**
  - `ext-curl`: Extensión nativa de PHP para peticiones HTTP/REST.
  - `App\Helpers\HardwareInfo`: Generador del bloque de hardware.
  - `App\Models\Dbconnection`: Extracción y borrado en base de datos.
- **Enlace a documentación de API:**
  - Contrato oficial: [`docs/apis/airflight/README.md`](../../apis/airflight/README.md).
  - Fundamentos: [`docs/apis/airflight/00-fundamentos.md`](../../apis/airflight/00-fundamentos.md).

---

## 5. Configuración Requerida
| Variable | Tipo | Valor por defecto | Efecto / Comportamiento |
|---|---|---|---|
| `API_URL` | `string` | `https://api.raupulus.dev/api/v2/airflight/aircrafts/batch` | URL canónica completa del endpoint batch. |
| `API_TOKEN` | `string` | (vacío) | Token de acceso Bearer (Laravel Sanctum). |
| `DEVICE_ID` | `int` | `null` | Identificador del dispositivo en el backend. |
| `BATCH_SIZE` | `int` | `100` | Número de aeronaves por petición (máx 500). |

---

## 6. Trampas y Consideraciones Técnicas
- **Redirección 301 de dominio legado:** El dominio `api.fryntiz.dev` redirige con 301 a `https://api.raupulus.dev`. Si cURL no tiene `CURLOPT_POSTREDIR = CURL_REDIR_POST_ALL`, cURL transforma la petición en `GET` tras el 301 y descarta el cuerpo POST. Por ello se fija la URL canónica directa en `.env`.
- **Envelope ApiResponseTrait:** La API no devuelve los datos planos; envuelve la respuesta en `{"success": true, "data": {...}}`. El script comprueba explícitamente `!empty($json['success'])`.

---

## 7. Tests que lo cubren
- `⚠️ sin verificar` (No existe suite de tests con mocks HTTP).

---

## 8. Pendiente real
- [ ] Implementar un test con mock de cURL o servidor HTTP local de pruebas para validar control de errores 422/500 y timeouts.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
