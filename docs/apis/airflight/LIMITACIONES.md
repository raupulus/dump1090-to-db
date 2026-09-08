# API AirFlight V2 — Limitaciones y Restricciones

Límites operativos, cuotas de procesamiento y restricciones de validación aplicadas por el backend.

---

## 1. Tamaño del Lote (`data`)

- **Mínimo:** 1 aeronave por petición.
- **Máximo permitido:** 500 aeronaves por petición.
- **Recomendación para Raspberry Pi 2:** Lotes de **100 aeronaves** (`BATCH_SIZE=100`). Procesa en ~1.5 a 3 segundos en hardware limitado sin generar picos de memoria ni timeouts.

---

## 2. Restricciones del Bloque `hardware_device_info.extra`

El campo `extra` permite enviar telemetría libre bajo las siguientes condiciones estrictas:
- **Máximo de claves:** 30 pares clave/valor.
- **Tipos admitidos:** Solo valores escalares primitivos (`string`, `integer`, `float`, `boolean`).
- **Prohibido:** Arrays anidados u objetos complejos.
- **Longitud máxima por valor:** 255 caracteres.

---

## 3. Timeouts y Red

- **Timeout de Conexión Recomendado:** 10 segundos.
- **Timeout Total de Petición Recomendado:** 30 segundos.
- **Idempotencia:** Los reportes de aeronaves se identifican por `icao` y marca de tiempo; si un lote se reenvía por corte de red, el backend actualiza la última posición sin duplicar la entidad principal de aeronave.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
