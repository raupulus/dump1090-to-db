# Módulo: HardwareInfo (`App\Helpers\HardwareInfo`)

Recolector de bajo consumo de telemetría de hardware, estado del sistema y sensores de la Raspberry Pi.

---

## 1. Qué hace y qué NO hace

### Qué hace
- Lee métricas del sistema operativo directamente desde pseudo-sistemas de ficheros en memoria (`/proc` y `/sys`) para evitar lanzar subprocesos pesados:
  - Temperatura de la CPU: `/sys/class/thermal/thermal_zone0/temp` (miligrados convertidos a °C).
  - Tiempo de actividad: `/proc/uptime`.
  - Uso de memoria RAM: `/proc/meminfo` (`MemTotal`, `MemAvailable`, `MemFree`).
  - Espacio en disco: `disk_total_space('/')` y `disk_free_space('/')`.
  - Carga de CPU: `sys_getloadavg()` (carga en 1m, 5m y 15m convertida a porcentaje estimado de CPU según núcleos disponibles).
  - IP local: Interfaz de red activa con salida a internet.
- Obtiene métricas específicas de silicio Raspberry Pi mediante `vcgencmd` (si está disponible en el PATH):
  - Voltaje del núcleo: `vcgencmd measure_volts core`.
  - Estado de throttling y subtensión: `vcgencmd get_throttled` (bits de undervoltage activo o histórico).
- Empaqueta el bloque `hardware_device_info` cumpliendo estrictamente el contrato API V2.

### Qué NO hace
- No envía las métricas por red; solo construye el payload estructurado para que lo consuma el transportador.
- No almacena históricos de telemetría en base de datos local.

---

## 2. Modelo de datos
- **Estructura del bloque `hardware_device_info`**:
  - `hardware_device_id` (`int|null`): ID numérico del dispositivo registrado en el backend.
  - `temp` (`float|null`): Temperatura del procesador en grados Celsius.
  - `voltage` (`float|null`): Voltaje del procesador en Voltios (V).
  - `cpu` (`float|null`): Porcentaje estimado de uso de CPU (0 - 100).
  - `disk` (`float|null`): Porcentaje de ocupación del disco principal (0 - 100).
  - `ram` (`float|null`): Porcentaje de ocupación de la memoria RAM (0 - 100).
  - `battery_level` (`null`): Reservado para dispositivos a batería (siempre `null` en RPi).
  - `uptime` (`int|null`): Segundos de actividad acumulados desde el arranque.
  - `ip_local` (`string|null`): Dirección IPv4 privada de la interfaz local.
  - `extra` (`array`): Diccionario clave-valor con métricas extendidas:
    - `buffer_reports` (`int`): Reportes aún pendientes en la base de datos local tras este lote.
    - `throttled` (`string|null`): Máscara hexadecimal de throttling (p. ej. `"0x50005"`).
    - `undervoltage_detected` (`bool`): Indicador booleano de subtensión registrada.
    - `load_1m`, `load_5m`, `load_15m` (`float`): Medias de carga del kernel.
    - `ram_free_mb` (`int`): Memoria disponible en Megabytes.
    - `disk_free_gb` (`float`): Espacio libre en Gigabytes.

---

## 3. Flujos principales
1. **Generación de Telemetría (`getPayload`)**:
   - Invocado con `$deviceId` y el número de reportes en cola (`$bufferReports`).
   - Comprueba disponibilidad de `/proc` y `/sys`.
   - Consulta el estado de voltaje y throttling vía `vcgencmd`.
   - Construye y retorna el array asociativo estructurado según el contrato de la API.

---

## 4. Puntos de entrada
- **Método estático principal:**
  - `HardwareInfo::getPayload(?int $deviceId = null, int $bufferReports = 0): array`

---

## 5. Dependencias en ambos sentidos
- **Depende de (Hacia adentro):**
  - Entorno Linux (`/sys`, `/proc`).
  - Utilidad opcional `vcgencmd` (presente en Raspberry Pi OS).
- **Consumido por (Hacia afuera):**
  - `src/upload_data_to_api.php`: Incrustado en el cuerpo JSON enviado a la API.

---

## 6. Configuración
| Variable | Tipo | Valor por defecto | Efecto / Comportamiento |
|---|---|---|---|
| `DEVICE_ID` | `int` | `null` | Identificador del nodo en el backend central. |

---

## 7. Trampas conocidas
- **`ip_public` omitida:** El contrato de la API prohíbe que el cliente envíe `ip_public` (la API la resuelve automáticamente desde la cabecera del socket HTTP). Si se incluye en `hardware_device_info`, el validador del backend rechaza la petición con `422 Unprocessable Entity`.
- **Límites en `extra`:** El backend solo admite valores escalares simples (string, int, float, bool) de máximo 255 caracteres y un total de hasta 30 claves.

---

## 8. Tests que lo cubren
- `⚠️ sin verificar` (No existe suite de tests automatizados configurada).

---

## 9. Pendiente real
- [ ] Implementar un test unitario simulando respuestas ficticias de `/proc/meminfo` y `vcgencmd`.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
