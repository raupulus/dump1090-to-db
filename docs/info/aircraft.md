# Módulo: Aircraft (`App\Models\Aircraft`)

Objeto de valor (DTO) plano que representa una aeronave con datos normalizados lista para persistencia o transporte.

---

## 1. Qué hace y qué NO hace

### Qué hace
- Encapsula los campos de una aeronave procesada: identificador ICAO, categoría, squawk, código de vuelo, coordenadas geográficas, altitud, rumbo, velocidad, fecha de avistamiento y métricas de radio (RSSI).
- Mapea dinámicamente las propiedades recibidas en su constructor.

### Qué NO hace
- No contiene lógica de negocio, cálculos de conversión ni validaciones complejas.
- No interactúa con base de datos ni red.

---

## 2. Modelo de datos
- **Propiedades públicas:**
  - `$icao` (`string`): Código hexadecimal ICAO de 24 bits.
  - `$category` (`string|null`): Categoría de la aeronave según estándar ADS-B.
  - `$squawk` (`string|null`): Código de transpondedor de 4 dígitos.
  - `$flight` (`string|null`): Identificador del vuelo / indicativo de llamada (callsign).
  - `$lat` (`float|null`): Latitud en grados decimales WGS84.
  - `$lon` (`float|null`): Longitud en grados decimales WGS84.
  - `$altitude` (`float|null`): Altitud en metros sobre el nivel del mar.
  - `$vert_rate` (`float|null`): Tasa de ascenso/descenso en metros por segundo.
  - `$track` (`float|null`): Rumbo en grados sexagesimales (0 - 360).
  - `$speed` (`float|null`): Velocidad horizontal en metros por segundo.
  - `$seen_at` (`string|null`): Fecha y hora del avistamiento formateada en ISO/SQL.
  - `$messages` (`int|null`): Contador total de mensajes recibidos de esta aeronave.
  - `$rssi` (`float|null`): Potencia relativa de la señal recibida (dBFS).
  - `$emergency` (`string|null`): Estado de emergencia declarado si aplica.

---

## 3. Flujos principales
1. **Construcción:**
   - Instanciado por `App\Models\Airflight` tras procesar y normalizar los campos de una aeronave.

---

## 4. Puntos de entrada
- **Constructor:**
  - `__construct(array $attributes = [])`

---

## 5. Dependencias en ambos sentidos
- **Depende de (Hacia adentro):**
  - Ninguna dependencia externa.
- **Consumido por (Hacia afuera):**
  - `App\Models\Airflight` (generador de las instancias).
  - `App\Models\Dbconnection` (consumidor para persistencia en tabla `reports`).

---

## 6. Configuración
No posee variables de configuración.

---

## 7. Trampas conocidas
- Las propiedades son públicas y dinámicas: no forzar tipos estrictos en el constructor para evitar roturas ante campos adicionales de nuevas versiones de `dump1090`.

---

## 8. Tests que lo cubren
- `⚠️ sin verificar` (No existe suite de tests automatizados configurada).

---

## 9. Pendiente real
- [ ] Incorporar `declare(strict_types=1)` o propiedades tipadas en PHP 8.1+ si se formaliza el DTO.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
