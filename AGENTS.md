# AGENTS.md

Guía de referencia rápida para agentes de IA (Claude Code, Cursor, Copilot, etc.) y colaboradores humanos que trabajen en este repositorio.

---

## 1. Resumen del Proyecto

`dump1090-to-db` es un conjunto de scripts PHP y utilidades del sistema que leen el archivo JSON volátil generado por `dump1090-fa` (u otro decodificador ADS-B compatible), normalizan los datos de cada aeronave detectada (unidades internacionales, timestamps) y los almacenan en PostgreSQL. Los registros acumulados se suben periódicamente en lotes (batch) a la API AirFlight V2 junto con telemetría de salud de hardware (`HardwareInfo`), purgándose de la base de datos local tras confirmación de éxito.

Optimizado para hardware de bajo consumo (Raspberry Pi 2) con receptor SDR RTL2832U, priorizando un uso mínimo de CPU y 0 escrituras redundantes en la tarjeta MicroSD.

---

## 2. Estructura del Repositorio

```text
dump1090-to-db/
├── src/                                  # Código PHP ejecutable y módulos
│   ├── dump1090_exporter.php             # CLI: lectura de dump1090 y persistencia local
│   ├── upload_data_to_api.php            # CLI: subida por lotes y telemetría a API V2
│   ├── Helpers/
│   │   ├── HardwareInfo.php              # Recolección de métricas de CPU/RAM/voltaje
│   │   └── Log.php                       # Logger ANSI condicionado a DEBUG
│   └── Models/
│       ├── Aircraft.php                  # DTO plano de aeronave normalizada
│       ├── Airflight.php                 # Parser y conversor de tramas ADS-B
│       ├── Api.php                       # Stub reservado (ver docs/future)
│       └── Dbconnection.php              # Capa PDO PostgreSQL (transacciones y purgas)
├── scripts/                              # Scripts Bash de control y mantenimiento
│   ├── start_dump1090_exporter.sh        # Proceso daemon en bucle continuo
│   ├── install_service.sh                # Instalador idempotente del servicio systemd
│   ├── installer.sh                      # Setup de dependencias y base de datos
│   ├── createdb.sh                       # Creación de BD e inyección de esquema
│   ├── optimize_pi.sh                    # Tuning de SO Raspberry Pi (tmpfs, fsync)
│   └── update_and_optimize.sh            # Actualización a Debian Trixie y limpieza
├── tests/                                # Directorio de pruebas automatizadas
│   └── .gitkeep
├── systemd/
│   └── dump1090-to-db.service.template   # Plantilla de servicio systemd
├── docs/                                 # Sistema de documentación técnica
│   ├── apis/                             # Documentación oficial destilada de terceros
│   │   └── airflight/                    # API AirFlight V2 (00-fundamentos, ERRATAS, src/)
│   ├── deploys/                          # Guías de despliegue (raspberry-pi, systemd)
│   ├── future/                           # Decisiones acordadas pero aplazadas
│   └── info/                             # Documentación técnica VIVA e índice maestro
├── db.sql                                # Esquema DDL de PostgreSQL (tabla reports)
├── composer.json                         # Dependencias y autoload PSR-4 ("App\\": "src/")
├── .env.example                          # Plantilla de variables de entorno
├── .gitignore                            # Ignora .env, vendor/, /docs/planning/, /docs/auditorias/
├── AGENTS.md                             # Este archivo: referencia operativa permanente
└── README.md                             # Guía general de usuario y presentación
```

---

## 3. Comandos Habituales

```bash
# Aprovisionamiento inicial (crea BD e instala composer)
./scripts/installer.sh

# Ejecución puntual del exportador (lectura dump1090 -> PostgreSQL)
php src/dump1090_exporter.php

# Ejecución puntual de la subida a la API (PostgreSQL -> API V2)
php src/upload_data_to_api.php

# Ejecución del daemon en primer plano (bucle continuo con trap)
./scripts/start_dump1090_exporter.sh

# Instalación / reinicio del servicio en systemd (producción)
sudo ./scripts/install_service.sh

# Monitorización del servicio en producción
systemctl status dump1090-to-db
journalctl -u dump1090-to-db -f
```

---

## 4. Convenciones del Código

- **PHP >= 8.0**: Tipado estricto donde aplique, PSR-12, autoload PSR-4 bajo `"App\\": "src/"`.
- **Idioma**: Documentación, comentarios y textos explicativos en **español**. Identificadores, nombres de fichero/directorio y mensajes de log en **inglés**.
- **Atribución**: Autor `Raúl Caro Pastorino`, nick `@raupulus`, email `public@raupulus.dev`. **Sin firmas de agentes** en commits, PRs ni documentación (nada de `Co-Authored-By`, «Generated with…» ni identificadores de sesión).
- **Mensajes de commit**: Neutros, técnicos y precisos. Prohibido incluir referencias a datos sensibles o expresiones como *"datos personales prohibidos"*.
- **Configuración**: Variables de entorno vía `symfony/dotenv` desde `.env` en la raíz (ver `.env.example`).
- **Logging**: Utilizar `App\Helpers\Log`, condicionado a la constante booleana `DEBUG`.

---

## 5. Trampas Conocidas y Puntos de Atención

| Componente | Trampa / Particularidad Técnica |
|---|---|
| PostgreSQL `reports.track` | El campo `track` es **FLOAT** (`double precision`). Definirlo como `INTEGER` provoca el error fatal `22P02` al recibir rumbos con decimales (p. ej. `45.5`). |
| Redirección 301 API | `api.fryntiz.dev` redirige con 301 a `https://api.raupulus.dev`. cURL requiere `CURLOPT_POSTREDIR = CURL_REDIR_POST_ALL` o usar directamente la URL canónica para no perder el payload POST. |
| Telemetría `ip_public` | No enviar `ip_public` en `hardware_device_info`; la API la resuelve del socket. Enviarla provoca error `422 Unprocessable Entity`. |
| Cuota en `extra` | El bloque `hardware_device_info.extra` solo admite hasta 30 claves escalares simples (string, int, float, bool) de máx 255 caracteres. |
| MicroSD en Raspberry Pi | Para evitar corrupción de tarjeta flash, PostgreSQL debe correr con `fsync = off` y logs en `tmpfs` RAM. |

---

## 6. Protocolo Residente de Documentación (`docs/`)

Este protocolo es de obligado cumplimiento en todas las tareas de este repositorio.

### Jerarquía de Verdad
1. **Código** (fuente primaria de comportamiento).
2. **`docs/info/`** (documentación técnica VIVA).
3. **`AGENTS.md`** (resumen operativo).
4. El resto (`README.md`, etc.).
*(Nota: `docs/planning/`, `docs/future/` y `docs/auditorias/` NUNCA son fuente de verdad del estado actual).*

### Reglas Permanentes
1. **Documentar es parte de la tarea**: Ninguna tarea está terminada si su documentación no se actualiza **en el mismo commit** que el código.
2. **Discrepancia detectada**: Se corrige en el commit en que se detecta, nunca se aplaza.
3. **Mantenimiento de módulos**: Tocas un módulo ➔ actualizas su `.md`. Creas uno ➔ desde `_MODULE_TEMPLATE.md` e indexado en `docs/info/README.md` y `AGENTS.md`. Eliminas uno ➔ borras su `.md` y lo quitas de todos los índices.
4. **Línea obligatoria de trazabilidad**: Todo archivo bajo `docs/` termina con esta línea exacta tras un separador `---`:
   ```markdown
   > Creado: YYYY-MM-DD · Última revisión: YYYY-MM-DD
   ```
5. **Carpetas efímeras (`/docs/planning/` y `/docs/auditorias/`)**:
   - Están en `.gitignore`.
   - Trabajo temporal de un desarrollador. Ciclo: *crear ➔ trabajar ➔ verificar ➔ promocionar lo duradero ➔ borrar*.
   - Nada versionado puede enlazar a estas carpetas.
6. **Lectura dirigida**: Al trabajar en un módulo lees **solo** su archivo `.md`. Si tocas una API externa lees `docs/apis/<api>/` en orden: `README.md` ➔ `00-fundamentos.md` + `ERRATAS.md` + `LIMITACIONES.md` ➔ el dominio necesario. No leas el resto de `docs/info/` ni archivos aplazados.
7. **Verificación empírica**: Nunca configurar APIs basándose en especificaciones no comprobadas. Lo que no se haya probado contra peticiones reales se marca como `⚠️ sin verificar`.

### Disparadores Operativos
- **Modificas módulo** ➔ Su `.md` en `docs/info/`.
- **Cambias contrato público** ➔ Documentar contrato y verificar clientes antes de romperlo.
- **Integras API de terceros** ➔ `docs/info/apis/<api>.md` enlazando a `docs/apis/<api>/` sin duplicar datos oficiales.
- **Añades script o comando** ➔ `docs/info/commands.md`.
- **Añades o quitas directorios** ➔ Árbol de estructura de `AGENTS.md`.
- **Decisión deliberada de arquitectura** ➔ `docs/info/decisiones-tecnicas.md`.
- **Idea acordada pero aplazada** ➔ `docs/future/`.

### Índice Maestro de `docs/info/`
- [`docs/info/README.md`](docs/info/README.md): Índice maestro.
- [`docs/info/_MODULE_TEMPLATE.md`](docs/info/_MODULE_TEMPLATE.md): Plantilla oficial de módulo.
- [`docs/info/commands.md`](docs/info/commands.md): Catálogo exhaustivo de comandos y scripts.
- [`docs/info/decisiones-tecnicas.md`](docs/info/decisiones-tecnicas.md): Registro cronológico de decisiones.
- [`docs/info/airflight.md`](docs/info/airflight.md): Módulo `App\Models\Airflight`.
- [`docs/info/aircraft.md`](docs/info/aircraft.md): Módulo `App\Models\Aircraft`.
- [`docs/info/dbconnection.md`](docs/info/dbconnection.md): Módulo `App\Models\Dbconnection`.
- [`docs/info/hardware-info.md`](docs/info/hardware-info.md): Módulo `App\Helpers\HardwareInfo`.
- [`docs/info/log.md`](docs/info/log.md): Módulo `App\Helpers\Log`.
- [`docs/info/apis/airflight.md`](docs/info/apis/airflight.md): Integración con AirFlight API V2.

### Checklist Antes de dar una Tarea por Terminada
- [ ] Documentación del módulo tocado actualizada en el **mismo commit**.
- [ ] Fechas de «Última revisión» al día en los archivos tocados.
- [ ] Módulos nuevos indexados en `docs/info/README.md` y en `AGENTS.md`.
- [ ] Módulos eliminados fuera de todos los índices.
- [ ] Ningún archivo versionado enlaza a `/docs/planning/` ni a `/docs/auditorias/`.
- [ ] `/docs/planning/` y `/docs/auditorias/` permanecen en `.gitignore`.
- [ ] Lo duradero de planificaciones cerrado y promocionado, y los archivos temporales borrados.
- [ ] Checklists `[x]` verificados empíricamente, no asumidos.
- [ ] Árbol de estructura de `AGENTS.md` refleja la realidad del disco.
