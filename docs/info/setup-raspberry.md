# Guía de Optimización para Raspberry Pi y Dump1090

Esta guía documenta los pasos necesarios para configurar desde cero una Raspberry Pi (especialmente modelos con recursos limitados como la RPi 2) dedicada de forma exclusiva a recibir señales ADS-B con `dump1090-fa` y subirlas a la API sin agotar la vida útil de la tarjeta MicroSD.

## Objetivo
- Eliminar la corrupción de la tarjeta SD.
- Desactivar funciones y servicios innecesarios (wifi, bluetooth, GUI).
- Afinar el rendimiento de `dump1090-fa` para SDRs (ganancia y corrección de errores).

---

## 1. Eliminar Riesgo de Corrupción de la Tarjeta SD

El uso continuo de la Raspberry Pi con bases de datos (PostgreSQL) y logs provoca un desgaste muy acelerado de la memoria flash de las tarjetas SD. Sigue estos pasos para solucionarlo:

### 1.1 Desactivar el Archivo de Intercambio (Swap)
La escritura de memoria en disco (Swap) destroza las SDs.
```bash
sudo dphys-swapfile swapoff
sudo dphys-swapfile uninstall
sudo systemctl disable dphys-swapfile
sudo apt-get purge -y dphys-swapfile
sudo rm -f /var/swap
```

### 1.2 Mover los Logs a la Memoria RAM (tmpfs)
Configuraremos el directorio `/var/log` para que resida en la memoria RAM, de forma que los registros de errores del sistema operativo no se guarden en la SD.
Añade la siguiente línea al final del archivo `/etc/fstab`:
```bash
tmpfs /var/log tmpfs defaults,noatime,nosuid,mode=0755,size=100m 0 0
```
Y ejecuta `sudo mount -a`.

### 1.3 Optimizar PostgreSQL
La base de datos realiza sincronizaciones (fsync) muy agresivas en la SD cada vez que se inserta un vuelo. Dado que en este proyecto los datos son efímeros (se borran tras subirse), podemos indicarle a Postgres que priorice la velocidad y no fuerce la escritura en disco usando la caché de RAM.
Edita `/etc/postgresql/*/main/postgresql.conf` y cambia las siguientes variables:
```ini
fsync = off
synchronous_commit = off
full_page_writes = off
stats_temp_directory = '/run/postgresql'
```
Reinicia el servicio: `sudo systemctl restart postgresql`.

---

## 2. Optimización del Sistema Operativo

Dado que esta Raspberry Pi funciona "Headless" (sin pantalla) y no tiene WiFi ni impresoras, debemos apagar los servicios no utilizados para ahorrar memoria RAM y CPU.

### 2.1 Desactivar Entorno Gráfico
Cambiaremos el sistema de arranque para que solo cargue la consola de comandos:
```bash
sudo systemctl set-default multi-user.target
```

### 2.2 Deshabilitar Bluetooth, WiFi y Redes Innecesarias
```bash
sudo systemctl disable wpa_supplicant bluetooth hciuart cups avahi-daemon
sudo systemctl stop wpa_supplicant bluetooth hciuart cups avahi-daemon
```

---

## 3. Configuración Óptima de Dump1090-fa

Edita el archivo de configuración en `/etc/default/dump1090-fa`.

- **Ganancia:** Cambiar a `RECEIVER_GAIN=49.6`. Un valor mayor como 60 activará el AGC (Control de ganancia automática), lo que subirá enormemente el ruido de fondo para el pincho RTL-SDR estándar y reducirá el alcance real.
- **Corrección de Errores:** Establecer a `ERROR_CORRECTION=yes`. Activar esto utiliza un poco más de CPU, pero recupera hasta un 10% más de mensajes débiles/corruptos de aviones muy lejanos.
- **Rango Adaptativo:** Establecer a `ADAPTIVE_DYNAMIC_RANGE=yes`. Esto ajustará dinámicamente la ganancia para que los aviones muy cercanos y fuertes no "cieguen" al receptor de radio.

Reinicia con: `sudo systemctl restart dump1090-fa`

---

## 4. Actualización del Sistema (Opcional pero recomendado)

Para migrar a Debian 13 (Trixie) modifica las listas de APT:
```bash
sudo sed -i 's/bookworm/trixie/g' /etc/apt/sources.list
sudo sed -i 's/bookworm/trixie/g' /etc/apt/sources.list.d/*.list
sudo apt-get update
sudo apt-get full-upgrade -y
sudo reboot
```
> **Nota:** Procede con precaución con las actualizaciones mayores si estás operando el dispositivo en remoto.
