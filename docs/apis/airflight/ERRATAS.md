# API AirFlight V2 — Erratas y Particularidades Verificadas

Registro de discrepancias entre la documentación inicial/convenciones asumidas y el comportamiento verificado en producción.

---

## 1. Redirección HTTP 301 del Dominio Antiguo

- **Comportamiento documentado inicialmente:** Uso del endpoint en `https://api.fryntiz.dev/api/v2/...`
- **Comportamiento real verificado:** `api.fryntiz.dev` emite una redirección permanente `HTTP 301 Moved Permanently` hacia `https://api.raupulus.dev`.
- **Impacto técnico:** Clientes cURL estándar transforman peticiones POST en GET tras un 301 y descartan el cuerpo del payload JSON, provocando un falso fallo silencioso.
- **Solución obligatoria:** Apuntar directamente al dominio canónico `https://api.raupulus.dev/api/v2/airflight/aircrafts/batch` y configurar `CURLOPT_POSTREDIR = CURL_REDIR_POST_ALL` en el cliente.

---

## 2. Rechazo de `ip_public` en `hardware_device_info`

- **Comportamiento asumido:** El cliente podría enviar su propia IP pública detectada.
- **Comportamiento real verificado:** La regla de validación del backend `DeviceStatusPayload` prohíbe el campo `ip_public` procedente del cliente. La API resuelve automáticamente la IP pública desde la cabecera del socket TCP de la petición entrante. Incluir `ip_public` provoca un fallo `422 Unprocessable Entity`.
- **Solución obligatoria:** Enviar únicamente `ip_local` y dejar que el servidor resuelva `ip_public`.

---

## 3. Rango y Tipo de Rumbo (`track`)

- **Comportamiento verificado:** El campo `track` admite números decimales / de punto flotante en el rango de `0` a `360` (p. ej. `45.5`).

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
