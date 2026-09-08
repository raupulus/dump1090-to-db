# Módulo: Airflight (`App\Models\Airflight`)

Procesador y normalizador de las tramas ADS-B decodificadas procedentes del archivo JSON de dump1090.

---

## 1. Qué hace y qué NO hace

### Qué hace
- Parsea el contenido crudo del array `aircraft` proveniente de `/run/dump1090-fa/aircraft.json`.
- Aplica transformaciones y conversiones de unidades normalizadas a métrica internacional:
  - Pies a metros (`feetToMeters`): altitud barométrica, altitud geométrica, tasa de ascenso vertical.
  - Nudos a metros por segundo (`knotsToMeters`): velocidad sobre tierra (`gs`), velocidad indicada (`ias`), velocidad verdadera (`tas`).
  - Segundos relativos a marca temporal absoluta (`timestampFromLastSeconds`): calcula la fecha exacta del último avistamiento (`seen_at`).
  - Conversión a entero (`toInt`): número de mensajes.
- Filtra tramas y genera una colección de objetos de valor `Aircraft` listos para ser persistidos.

### Qué NO hace
- No lee directamente del disco ni gestiona sockets; recibe los datos ya decodificados en un array asociativo.
- No realiza consultas a base de datos ni invoca APIs remotas.

---

## 2. Modelo de datos
- **Propiedades internas:**
  - `$startAt` (`\Carbon\Carbon`): Marca de tiempo base de referencia para el cálculo de fechas relativas.
  - `$attributes` (`array`): Diccionario de atributos admitidos y sus métodos de saneado asociados.
  - `$aircraft` (`array`): Colección resultante de instancias de `App\Models\Aircraft`.
  - `$debug` (`bool`): Indicador de depuración.

---

## 3. Flujos principales
1. **Instanciación y Normalización:**
   - Se invoca `new Airflight($jsonData, $now, $debug)`.
   - Se fija `$this->startAt` usando Carbon (a partir de `$now` o la hora actual).
   - Itera sobre cada elemento del array `$jsonData['aircraft']`.
   - Para cada elemento, ejecuta `cleanAircraft()`, iterando por las reglas de `$attributes`.
   - Si la aeronave contiene un identificador válido (`hex`), instancia un `Aircraft` y lo añade a `$this->aircraft`.

---

## 4. Puntos de entrada
- **Constructor:**
  - `__construct(array $datas = [], $startAt = null, bool $debug = false)`
- **Propiedad pública:**
  - `$aircraft`: Array público que expone los objetos `Aircraft` normalizados.

---

## 5. Dependencias en ambos sentidos
- **Depende de (Hacia adentro):**
  - `Carbon\Carbon` (para gestión de marcas temporales).
  - `App\Models\Aircraft` (instanciado como objeto de salida).
- **Consumido por (Hacia afuera):**
  - `src/dump1090_exporter.php` (tras leer el JSON de dump1090).

---

## 6. Configuración
No requiere configuración directa propia; toma el parámetro `$debug` de la constante global `DEBUG`.

---

## 7. Trampas conocidas
- **Unidades métricas vs aeronáuticas:** Los datos se normalizan a metros y m/s. Si un consumidor espera pies o nudos, debe realizar la conversión inversa o consultar directamente el JSON en crudo.
- **Preservación de ceros numéricos:** Valores numéricos equivalentes a cero (`speed = 0`, `track = 0` indicando rumbo norte o `altitude = 0` a nivel de suelo) deben verificarse con `$val !== null && $val !== ''` para evitar que un condicional simple los anule.
- **Conversión de tasas verticales a m/s:** Las tasas verticales (`baro_rate` y `geom_rate` recibidas en pies/min) se convierten a metros por segundo (`m/s`) mediante `(fpm / 3.281) / 60`, preservando ascensos positivos y descensos negativos.
- **Campos nulos:** Muchos atributos son opcionales en tramas ADS-B (p. ej. `lat`, `lon` o `squawk` pueden ser `null` si el transpondedor no los ha transmitido aún).

---

## 8. Tests que lo cubren
- `⚠️ sin verificar` (No existe suite de tests automatizados configurada).

---

## 9. Pendiente real
- [ ] Implementar pruebas unitarias con tramas ADS-B sintéticas y reales representativas en `tests/`.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
