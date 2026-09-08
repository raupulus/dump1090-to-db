# Módulo: Dbconnection (`App\Models\Dbconnection`)

Capa de acceso a datos y abstracción PDO sobre la base de datos PostgreSQL local (`dump1090`).

---

## 1. Qué hace y qué NO hace

### Qué hace
- Gestiona la conexión persistente o bajo demanda a PostgreSQL utilizando PDO con reintentos configurables.
- Inserta colecciones de aeronaves (`saveAirflight`) mediante consultas preparadas con parámetros seguros.
- Extrae los últimos reportes por lote (`getLastsAirflight`) ordenados cronológicamente descendente para su envío a la API externa.
- Elimina registros confirmados (`deleteAirflight`) tras una subida exitosa.
- Cuenta el total de reportes acumulados pendientes (`countPendingReports`).
- Purga registros antiguos que excedan un umbral de horas (`purgeOldAirflights`) para evitar saturación de la memoria RAM o disco.

### Qué NO hace
- No gestiona migraciones automáticas del esquema (el esquema se inicializa con `db.sql`).
- No interactúa con la API externa ni formatea payloads JSON.

---

## 2. Modelo de datos
- **Tabla afectada**: `reports`
  - `id`: `BIGSERIAL PRIMARY KEY`
  - `icao`, `category`, `squawk`, `flight`, `emergency`: `VARCHAR(100)`
  - `lat`, `lon`, `altitude`, `vert_rate`, `track`, `speed`, `rssi`: `FLOAT` (`double precision`)
  - `seen_at`: `TIMESTAMP`
  - `messages`: `INTEGER`

---

## 3. Flujos principales
1. **Inserción de Vuelos (`saveAirflight`)**:
   - Itera sobre el array de objetos `Aircraft` y ejecuta `INSERT INTO reports (...) VALUES (...)`.
2. **Extracción para Lotes (`getLastsAirflight`)**:
   - Ejecuta `SELECT ... FROM reports ORDER BY seen_at DESC LIMIT $limit` (máximo 500).
3. **Borrado Transaccional (`deleteAirflight`)**:
   - Recibe un array de IDs y ejecuta `DELETE FROM reports WHERE id IN (?, ?, ...)` usando prepared statements.
4. **Purga Preventiva (`purgeOldAirflights`)**:
   - Ejecuta `DELETE FROM reports WHERE seen_at < NOW() - INTERVAL '$hours hours'`.

---

## 4. Puntos de entrada
- **Constructor:**
  - `__construct(array $params = [])`: Inicializa parámetros y conecta a PDO con hasta 10 reintentos espaciados por 1 segundo.
- **Métodos públicos:**
  - `saveAirflight(array $airflights): void`
  - `getLastsAirflight(int $limit = 100): \PDOStatement|null`
  - `deleteAirflight(array $ids): bool`
  - `countPendingReports(): int`
  - `purgeOldAirflights(int $hours = 2): int`
  - `close(): bool`

---

## 5. Dependencias en ambos sentidos
- **Depende de (Hacia adentro):**
  - `PDO` y `PDOStatement` (extensión nativa de PHP).
  - `App\Helpers\Log` (para emisión de errores de conexión).
- **Consumido por (Hacia afuera):**
  - `src/dump1090_exporter.php`: Inserción de capturas y purga periódica.
  - `src/upload_data_to_api.php`: Lectura de lote a subir y borrado de IDs confirmados.

---

## 6. Configuración
| Variable | Tipo | Valor por defecto | Efecto / Comportamiento |
|---|---|---|---|
| `DB_CONNECTION` | `string` | `pgsql` | Driver PDO utilizado (`pgsql`). |
| `DB_HOST` | `string` | `127.0.0.1` | Dirección del servidor de base de datos. |
| `DB_PORT` | `int` | `5432` | Puerto del motor PostgreSQL. |
| `DB_DATABASE` | `string` | `dump1090` | Nombre de la base de datos. |
| `DB_USERNAME` | `string` | `pi` | Usuario de conexión. |
| `DB_PASSWORD` | `string` | (vacío) | Contraseña de conexión. |

---

## 7. Trampas conocidas
- **Columna `track`:** Históricamente era `INTEGER`, lo que causaba el error `22P02 (invalid input syntax for type integer: "45.5")`. Ahora es obligatoriamente `FLOAT` / `double precision`.
- **Rendimiento en MicroSD:** Para evitar desgaste prematuro de la tarjeta flash, la base de datos debe operar sobre tmpfs o con `fsync=off` (ver guía de despliegue en Raspberry Pi).

---

## 8. Tests que lo cubren
- `⚠️ sin verificar` (No existe suite de tests automatizados configurada).

---

## 9. Pendiente real
- [ ] Implementar un test de integración con una base de datos SQLite en memoria para validar las operaciones de lectura, inserción y purga.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
