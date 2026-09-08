# Documentación Técnica Viva (`docs/info`)

Índice maestro de la arquitectura, módulos, comandos y decisiones técnicas del proyecto `dump1090-to-db`.

---

## 1. Módulos del Sistema (`src/`)

- [**Airflight (`airflight.md`)**](airflight.md): Parser, saneador y conversor de tramas ADS-B desde `aircraft.json`.
- [**Aircraft (`aircraft.md`)**](aircraft.md): Objeto de valor (DTO) con los datos normalizados de la aeronave.
- [**Dbconnection (`dbconnection.md`)**](dbconnection.md): Capa de acceso a PostgreSQL (`reports`), transacciones y purgas de memoria.
- [**HardwareInfo (`hardware-info.md`)**](hardware-info.md): Recolector de telemetría de hardware, sensores y salud de la Raspberry Pi.
- [**Log (`log.md`)**](log.md): Logger ANSI ligero condicionado al flag `DEBUG`.

---

## 2. Operaciones y Comandos

- [**Catálogo de Comandos (`commands.md`)**](commands.md): Guía de scripts CLI, scripts de control (`scripts/`) y comandos del servicio systemd.

---

## 3. Decisiones Técnicas y Arquitectura

- [**Registro de Decisiones Técnicas (`decisiones-tecnicas.md`)**](decisiones-tecnicas.md): Historial cronológico de cambios de diseño, resolución de deuda técnica y arquitectura del sistema.

---

## 4. Integraciones Externas

- [**Integración API AirFlight V2 (`apis/airflight.md`)**](apis/airflight.md): Cómo interactúa el exportador con el backend remoto en subidas por lotes.

---

## 5. Plantillas

- [**Plantilla de Módulo (`_MODULE_TEMPLATE.md`)**](_MODULE_TEMPLATE.md): Estándar obligatorio para documentar cualquier módulo nuevo en este directorio.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
