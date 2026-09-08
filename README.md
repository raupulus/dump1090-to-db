# Exportador a Base de Datos desde dump1090 (`dump1090-to-db`)

Herramienta ligera en PHP que lee el flujo JSON de aeronaves generado por `dump1090-fa` (o cualquier decodificador ADS-B compatible), normaliza cada registro detectado y lo almacena en una base de datos PostgreSQL. Los registros acumulados se suben opcionalmente de forma periódica en lotes a una API remota (API V2) junto con la telemetría de salud del dispositivo, purgándose de la base de datos local tras su confirmación.

Diseñado para ejecutarse en dispositivos de bajo consumo (p. ej. Raspberry Pi) junto a un receptor ADS-B RTL-SDR.

Repositorio: [https://gitlab.com/raupulus/dump1090-to-db.git](https://gitlab.com/raupulus/dump1090-to-db.git)

---

## Tabla de Contenidos

- [Características](#características)
- [Requisitos](#requisitos)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Variables de Entorno](#variables-de-entorno)
- [Instalación](#instalación)
- [Arranque Manual](#arranque-manual)
- [Arranque Automático](#arranque-automático)
- [Campos del JSON de dump1090](#campos-del-json-de-dump1090)
- [Documentación Técnica](#documentación-técnica)
- [Autor](#autor)
- [Licencia](#licencia)

---

## Características

- [x] Instalador automático de base de datos (creación de BD y tablas).
- [x] Modelo de datos Airflight con normalización de unidades métricas internacionales.
- [x] Ingesta en tiempo real desde el JSON de dump1090 en RAM.
- [x] Instalador automático de dependencias PHP con Composer.
- [x] Configuración centralizada mediante `.env`.
- [x] Persistencia segura en PostgreSQL mediante consultas preparadas PDO.
- [x] Subida por lotes (Batch) a API V2 con reporte de telemetría de hardware (`HardwareInfo`).
- [x] Servicio systemd con arranque automático y reinicio ante fallos.

---

## Requisitos

- PHP >= 8.0 (con extensiones `ext-pdo` y `ext-curl`).
- PostgreSQL >= 13.
- Composer >= 2.0.11.

---

## Estructura del Proyecto

```text
.
├── src/                                  # Código fuente PHP y puntos de entrada
│   ├── dump1090_exporter.php             # Lee aircraft.json y persiste en la BD local
│   ├── upload_data_to_api.php            # Sube lotes a la API V2 y purga la BD local
│   ├── Helpers/                          # HardwareInfo (sensores/salud), Log (consola)
│   └── Models/                           # Airflight, Aircraft, Dbconnection, Api
├── scripts/                              # Scripts Bash de automatización y mantenimiento
│   ├── start_dump1090_exporter.sh        # Bucle daemon continuo del servicio
│   ├── install_service.sh                # Instala y activa el servicio en systemd
│   ├── installer.sh / createdb.sh        # Aprovisionamiento de BD y dependencias
│   ├── optimize_pi.sh                    # Afinación de Raspberry Pi (tmpfs, fsync)
│   └── update_and_optimize.sh            # Actualización de distribución y limpieza
├── tests/                                # Directorio de pruebas automatizadas
├── systemd/                              # Plantilla de unidad para systemd
├── db.sql                                # Esquema DDL para la tabla reports
└── docs/                                 # Protocolo de documentación técnica (info, apis, deploys, future)
```

Consulta [AGENTS.md](AGENTS.md) para un desglose detallado de arquitectura, convenciones y protocolo operativo para agentes y colaboradores.

---

## Variables de Entorno

| Variable | Descripción | Valor por defecto |
|---|---|---|
| `PATH_TO_AIRCRAFT_JSON` | Ruta absoluta al archivo `aircraft.json` de dump1090 | `/run/dump1090-fa/aircraft.json` |
| `DB_CONNECTION` | Driver PDO utilizado | `pgsql` |
| `DB_HOST` | Dirección del host de base de datos | `127.0.0.1` |
| `DB_PORT` | Puerto de conexión a la base de datos | `5432` |
| `DB_DATABASE` | Nombre de la base de datos | `dump1090` |
| `DB_USERNAME` | Usuario de la base de datos | `dbuser` |
| `DB_PASSWORD` | Contraseña de la base de datos | — |
| `API_URL` | Endpoint HTTP para la subida de lotes | `https://api.raupulus.dev/api/v2/airflight/aircrafts/batch` |
| `API_TOKEN` | Token Bearer de autenticación Sanctum | — |
| `DEVICE_ID` | Identificador del hardware/dispositivo registrado | — |
| `BATCH_SIZE` | Cantidad máxima de aeronaves por lote | `100` |
| `DEBUG` | Activa mensajes detallados en consola | `false` |

Plantilla de referencia disponible en `.env.example` (`cp .env.example .env`).

---

## Instalación

1. Configura el archivo de variables de entorno `.env` e instala las dependencias previas de software.
2. Ejecuta el instalador para crear la base de datos, aplicar el esquema y resolver las dependencias de Composer:

```bash
./scripts/installer.sh
```

---

## Arranque Manual

Método recomendado (bucle supervisor daemon):

```bash
./scripts/start_dump1090_exporter.sh
```

Ejecución puntual de componentes PHP individuales:

```bash
php src/dump1090_exporter.php
php src/upload_data_to_api.php
```

---

## Arranque Automático

### Servicio Systemd (Recomendado en Producción)

El script `scripts/install_service.sh` instala y arranca `dump1090-to-db` como servicio de systemd: resuelve dependencias si faltan, crea el `.env` desde `.env.example` si es necesario, compila el archivo de unidad y lo habilita e inicia automáticamente:

```bash
sudo ./scripts/install_service.sh
```

Comandos habituales de control:

```bash
systemctl status dump1090-to-db       # Consultar estado del servicio
journalctl -u dump1090-to-db -f       # Seguir logs en tiempo real
sudo systemctl restart dump1090-to-db # Reiniciar el servicio
sudo systemctl stop dump1090-to-db    # Detener el servicio
sudo systemctl disable dump1090-to-db # Deshabilitar arranque automático
```

El servicio ejecuta `scripts/start_dump1090_exporter.sh` en bucle continuo (`Type=simple`), se reinicia de manera automática ante caídas (`Restart=on-failure`) y sincroniza su arranque con la disponibilidad de la red y PostgreSQL (`After=network-online.target`). Más detalles en [docs/deploys/systemd.md](docs/deploys/systemd.md).

### Cron (Alternativa Legada)

En sistemas sin systemd es posible configurar el arranque tras reinicio mediante `@reboot` en `/etc/crontab`:

```text
@reboot pi sleep 40 && cd /home/pi/git/dump1090-to-db && ./scripts/start_dump1090_exporter.sh >> /tmp/dump1090.log 2>&1
```

*(Nota: Este enfoque no reinicia el proceso automáticamente si ocurre un error imprevisto).*

---

## Campos del JSON de dump1090

- `hex` → Identificador ICAO de 24 bits (6 caracteres hexadecimales).
- `squawk` → Código transpondedor seleccionado (representación octal de 4 dígitos).
- `flight` → Callsign o indicativo de llamada del vuelo.
- `lat`, `lon` → Latitud y longitud en grados decimales (WGS84).
- `nucp` → Categoría de incertidumbre de navegación para la posición.
- `seen_pos` → Segundos transcurridos desde el último reporte de posición.
- `altitude` → Altitud barométrica en pies (o `"ground"` si está en tierra).
- `vert_rate` → Tasa de ascenso/descenso en pies por minuto.
- `track` → Rumbo verdadero sobre el terreno en grados (0 - 360).
- `speed` → Velocidad horizontal reportada en nudos (kt).
- `messages` → Total de mensajes ADS-B recibidos de esta aeronave.
- `seen` → Segundos transcurridos desde el último mensaje recibido de este avión.
- `rssi` → Potencia de señal relativa promedio en dBFS (valor negativo).

---

## Documentación Técnica

La documentación técnica extendida, arquitectura detallada, integraciones de API y registro de decisiones se mantienen en **[docs/info/](docs/info/)**.

En [AGENTS.md](AGENTS.md) se encuentra el resumen operativo permanente y el protocolo de documentación para colaboradores y agentes de IA.

---

## Autor

- Nombre: Raúl Caro Pastorino
- Web: [raupulus.dev](https://raupulus.dev)
- Twitter: [@raupulus](https://twitter.com/raupulus)

---

## Licencia

[GNU General Public License v3.0](LICENSE)
