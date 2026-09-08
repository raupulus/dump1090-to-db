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
- **Tabla afectada**: `reports` (Buffer temporal para la API)
  - `id`: `BIGSERIAL PRIMARY KEY`
  - `icao`, `category`, `squawk`, `flight`, `emergency`: `VARCHAR(100)`
  - `lat`, `lon`, `altitude`, `vert_rate`, `track`, `speed`, `rssi`: `FLOAT` (`double precision`)
  - `seen_at`: `TIMESTAMP`
  - `messages`: `INTEGER`
- **Tabla afectada**: `aircraft_state` (Control de estado y desduplicación de aviones congelados)
  - `icao`: `VARCHAR(100) PRIMARY KEY`
  - `messages`: `INTEGER NOT NULL`
  - `updated_at`: `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`

---

## 3. Flujos principales
1. **Inicialización Preventiva (`ensureTablesExist`)**:
   - Garantiza que `reports` y `aircraft_state` existan tras arrancar PostgreSQL en RAM (tmpfs).
2. **Detección de Variaciones y Desduplicación (`getAircraftStates` / `upsertAircraftStates`)**:
   - `getAircraftStates()` devuelve el mapa `[icao => messages]` previo para omitir aeronaves congeladas.
   - `upsertAircraftStates()` actualiza en bloque el contador de mensajes usando `ON CONFLICT (icao) DO UPDATE`.
3. **Inserción de Vuelos (`saveAirflight`)**:
   - Itera sobre el array de objetos `Aircraft` con variaciones y ejecuta `INSERT INTO reports (...) VALUES (...)`.
4. **Extracción para Lotes (`getLastsAirflight`)**:
   - Ejecuta `SELECT ... FROM reports ORDER BY seen_at DESC LIMIT $limit` (máximo 500).
5. **Borrado Transaccional (`deleteAirflight`)**:
   - Recibe un array de IDs y ejecuta `DELETE FROM reports WHERE id IN (?, ?, ...)` usando prepared statements.
6. **Purga Preventiva (`purgeOldAirflights` / `purgeOldAircraftStates`)**:
   - Purga reportes antiguos de `reports` (umbral configurable, por defecto 2h) y estados inactivos de `aircraft_state` (1h).

---

## 4. Puntos de entrada
- **Constructor:**
  - `__construct(array $params = [])`: Inicializa parámetros, conecta a PDO con hasta 10 reintentos y asegura la presencia de las tablas (`ensureTablesExist`).
- **Métodos públicos:**
  - `ensureTablesExist(): void`
  - `getAircraftStates(): array<string, int>`
  - `upsertAircraftStates(array $states): void`
  - `purgeOldAircraftStates(int $hours = 1): mixed`
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
