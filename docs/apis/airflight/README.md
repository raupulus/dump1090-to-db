# API AirFlight V2 — Documentación Oficial Destilada

Documentación de referencia externa destilada y verificada de la API AirFlight V2 (`api.raupulus.dev`).

---

## 1. Estructura de Documentos

- [**00-fundamentos.md**](00-fundamentos.md): Autenticación Sanctum, cabeceras requeridas, formato de envelope `ApiResponseTrait` y convenciones de respuesta.
- [**ERRATAS.md**](ERRATAS.md): Erratas, discrepancias detectadas frente al comportamiento real en producción y cómo sortearlas.
- [**LIMITACIONES.md**](LIMITACIONES.md): Límites de tasa, tamaño máximo de lote, tipos estrictos y timeouts recomendados.
- [**batch-upload.md**](batch-upload.md): Especificación del dominio de subida por lotes (`/api/v2/airflight/aircrafts/batch`) y telemetría de hardware.
- [**src/contrato-v2.md**](src/contrato-v2.md): Fuente original inmutable del contrato facilitada por el responsable de la API.

---

## 2. Metadatos de Verificación
- **URL Base Oficial:** `https://api.raupulus.dev`
- **Fecha de Descarga/Integración:** 2026-09-08
- **Fecha de Verificación Real con Peticiones:** 2026-09-08 (Confirmada respuesta `201 Created` en producción).

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
