# Módulo: Log (`App\Helpers\Log`)

Logger ligero para consola CLI con soporte de formato y colores ANSI condicionado al modo de depuración.

---

## 1. Qué hace y qué NO hace

### Qué hace
- Emite mensajes formateados con colores en la salida estándar (`stdout`) de la consola:
  - `info` (Azul / Cyan)
  - `success` (Verde)
  - `warning` (Amarillo)
  - `error` / `danger` (Rojo)
- Si la constante booleana `DEBUG` es `false`, suprime la salida para mantener los registros del sistema limpios y optimizar rendimiento.

### Qué NO hace
- No rota archivos de log en disco ni escribe directamente en `/var/log` (la persistencia y rotación la delega a systemd / `journald`).
- No gestiona niveles complejos de severidad PSR-3.

---

## 2. Modelo de datos
- No maneja entidades de base de datos ni modelos de datos estructurados.

---

## 3. Flujos principales
1. **Emisión condicional de logs:**
   - Si `DEBUG === true`, envuelve el mensaje en la secuencia de escape ANSI correspondiente y realiza `echo $message . PHP_EOL`.
   - Si `DEBUG === false`, descarta la operación silenciosamente.

---

## 4. Puntos de entrada
- **Métodos estáticos:**
  - `Log::info(string $message): void`
  - `Log::success(string $message): void`
  - `Log::warning(string $message): void`
  - `Log::error(string $message): void`
  - `Log::danger(string $message): void`

---

## 5. Dependencias en ambos sentidos
- **Depende de (Hacia adentro):**
  - Constante global `DEBUG`.
- **Consumido por (Hacia afuera):**
  - `src/dump1090_exporter.php`
  - `src/upload_data_to_api.php`
  - `src/Models/Dbconnection.php`

---

## 6. Configuración
| Variable | Tipo | Valor por defecto | Efecto / Comportamiento |
|---|---|---|---|
| `DEBUG` | `bool` | `false` | Habilita o deshabilita la salida detallada por consola. |

---

## 7. Trampas conocidas
- En entornos no interactivos o terminales que no soportan colores ANSI, las secuencias de escape pueden visualizarse como caracteres literales (p. ej. `\033[0;32m`). En systemd journald se parsean correctamente o se descartan las secuencias.

---

## 8. Tests que lo cubren
- `⚠️ sin verificar` (No existe suite de tests automatizados configurada).

---

## 9. Pendiente real
- [ ] Incorporar detección de TTY (`posix_isatty(STDOUT)`) para omitir colores si no hay terminal interactiva.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
