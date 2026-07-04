# AGENTS.md

Guía de referencia rápida para agentes de IA (Claude Code, Cursor, Copilot, etc.) y colaboradores humanos que trabajen en este repositorio.

## Resumen del proyecto

`dump1090-to-db` es un conjunto de scripts PHP que leen el fichero JSON generado por `dump1090-fa` (u otro decodificador ADS-B compatible), normalizan los datos de cada aeronave detectada (unidades, timestamps) y los almacenan en PostgreSQL. Opcionalmente, los registros acumulados se suben de forma periódica a una API externa y se purgan de la base de datos local tras el envío.

Pensado para ejecutarse en dispositivos de bajo consumo (p. ej. Raspberry Pi) junto a un receptor ADS-B.

## Arquitectura y responsabilidades

| Fichero | Responsabilidad |
|---|---|
| `dump1090_exporter.php` | Punto de entrada principal. Carga `.env`, comprueba que exista el JSON, construye un `Airflight` y persiste los registros con `Dbconnection::saveAirflight()`. |
| `Models/Airflight.php` | Parsea y sanea el JSON crudo por aeronave: conversión de unidades (pies→metros, nudos→m/s), cálculo de timestamps relativos, y produce un array de `Aircraft`. |
| `Models/Aircraft.php` | Objeto de valor: representa una aeronave ya normalizada. |
| `Models/Dbconnection.php` | Envoltorio ligero sobre PDO. Construye y ejecuta las queries SQL. |
| `Models/Api.php` | Stub vacío, sin uso actual. |
| `Helpers/Log.php` | Logger trivial basado en `echo`, condicionado a la constante `DEBUG`. |
| `upload_data_to_api.php` | Punto de entrada independiente: lee los últimos registros de la BD, los envía por POST a `API_URL` con token bearer y `DEVICE_ID`, y borra los subidos si la API responde con éxito. |
| `start_dump1090_exporter.sh` | Bucle infinito que ejecuta el exportador y, cada `T_INTERVAL_UPLOAD_API` iteraciones (leído de `.env`, por defecto 3), el uploader. Es el proceso de entrada del servicio systemd. |
| `installer.sh` / `createdb.sh` | Aprovisionamiento: crea la BD de Postgres + tabla `reports` (ver `db.sql`) e instala dependencias de composer. |
| `install_service.sh` | Instalador del servicio systemd: resuelve dependencias/`.env` si faltan, genera la unit desde `systemd/dump1090-to-db.service.template` y hace `enable` + `start`. |
| `systemd/dump1090-to-db.service.template` | Plantilla de unit systemd (placeholders `__WORKING_DIR__` y `__SERVICE_USER__`, sustituidos por `install_service.sh`). |

## Comandos habituales

```bash
./installer.sh                     # crea BD + instala dependencias composer
composer install                   # solo dependencias
php dump1090_exporter.php          # ejecuta una iteración de exportación
php upload_data_to_api.php         # sube y purga registros pendientes
./start_dump1090_exporter.sh       # bucle continuo (ejecución manual/foreground)
sudo ./install_service.sh          # instala y arranca el servicio systemd (uso en producción)
```

No hay suite de tests automatizados ni linter configurado todavía en este proyecto.

## Convenciones del código

- PHP >= 8.0, autoload PSR-4 bajo `App\` + classmap para `Helpers/` y `Models/` (ver `composer.json`).
- Los comentarios y PHPDoc del código están en español; mantener ese idioma al modificar estos ficheros.
- Configuración vía variables de entorno cargadas con `symfony/dotenv` desde `.env` (ver `.env.example`).
- Los mensajes de log usan `Helpers\Log` y siempre están condicionados a la constante `DEBUG`.

## Puntos de atención conocidos

Detalle ampliado en [docs/info/decisiones.md](docs/info/decisiones.md):

- `Models/Api.php` es un stub sin funcionalidad.
- El servicio systemd (`install_service.sh`) usa `Type=simple` sobre un bucle `while true` en bash; si se necesitara parada más fina ante señales (por ejemplo, esperar a que termine una subida a la API en curso antes de matar el proceso), habría que añadir manejo explícito de `SIGTERM` en `start_dump1090_exporter.sh`. Con la configuración actual (`TimeoutStopSec` por defecto de systemd), es suficiente para el caso de uso.

## Documentación técnica y decisiones

Toda la documentación técnica ampliada (arquitectura en detalle, integraciones externas, deuda técnica) y el registro de decisiones se mantiene en **[`docs/info/`](docs/info/)** en formato Markdown. Cuando se tome una decisión de diseño relevante o se documente un aspecto técnico nuevo del proyecto, debe añadirse ahí (no en este archivo), que se mantiene como resumen operativo estable para agentes.
