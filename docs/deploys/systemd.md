# Despliegue: Servicio Systemd (`dump1090-to-db.service`)

Guía técnica para la instalación, gestión y ciclo de vida del servicio systemd que mantiene en ejecución permanente el exportador ADS-B.

---

## 1. Arquitectura del Servicio

El servicio systemd se define como `Type=simple` y ejecuta el script supervisor `scripts/start_dump1090_exporter.sh`.
- **Plantilla:** `systemd/dump1090-to-db.service.template`
- **Ubicación en producción:** `/etc/systemd/system/dump1090-to-db.service`
- **Dependencias del sistema:** `After=network-online.target postgresql.service`

---

## 2. Instalación y Despliegue Automático

Para instalar o reinstalar el servicio tras cambios en las rutas del repositorio:
```bash
sudo ./scripts/install_service.sh
```

El script realiza de forma idempotente las siguientes acciones:
1. Detecta el usuario no-root que invocó sudo (`$SUDO_USER` o `pi`).
2. Comprueba dependencias de Composer y esquema de base de datos.
3. Genera el archivo `.env` a partir de `.env.example` si no existía.
4. Compila la plantilla sustituyendo `__WORKING_DIR__` y `__SERVICE_USER__`.
5. Copia el archivo a `/etc/systemd/system/dump1090-to-db.service`.
6. Recarga la configuración con `systemctl daemon-reload`.
7. Habilita el arranque automático (`enable`) y arranca el servicio (`restart`).

---

## 3. Comandos de Gestión Operativa

| Operación | Comando |
|---|---|
| Comprobar estado y PID | `systemctl status dump1090-to-db` |
| Seguir logs en tiempo real | `journalctl -u dump1090-to-db -f` |
| Inspeccionar últimos fallos | `journalctl -u dump1090-to-db -e --no-pager` |
| Reiniciar daemon | `sudo systemctl restart dump1090-to-db` |
| Detener daemon | `sudo systemctl stop dump1090-to-db` |

---

## 4. Política de Reinicio y Salvaguardas

- **Reinicio ante fallos:** `Restart=on-failure` con `RestartSec=5`.
- **Protección contra bucles:** `StartLimitBurst=5` en `StartLimitIntervalSec=60`. Si el servicio falla 5 veces consecutivas en menos de un minuto (p. ej. credenciales erróneas de BD), systemd entra en estado `failed` para no saturar la CPU.
- **Señales:** El script `scripts/start_dump1090_exporter.sh` captura `SIGTERM` y `SIGINT` saliendo de inmediato con código 0, lo que permite paradas y reinicios limpios en menos de 1 segundo.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
