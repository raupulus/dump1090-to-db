#!/bin/bash
set -e

echo "=== 1. OPTIMIZANDO HARDWARE Y SD ==="
sudo dphys-swapfile swapoff || true
sudo systemctl disable dphys-swapfile || true
if ! grep -q "/var/log" /etc/fstab; then
    echo "tmpfs /var/log tmpfs defaults,noatime,nosuid,mode=0755,size=50m 0 0" | sudo tee -a /etc/fstab
fi
CONF=$(find /etc/postgresql -name postgresql.conf | head -n 1)
if [ -n "$CONF" ]; then
    sudo sed -i 's/^#fsync = on/fsync = off/' "$CONF"
    sudo sed -i 's/^#synchronous_commit = on/synchronous_commit = off/' "$CONF"
    sudo sed -i "s|^#stats_temp_directory = '.*'|stats_temp_directory = '/run/postgresql'|" "$CONF"
fi
if ! grep -q "max_usb_current=1" /boot/firmware/config.txt; then
    echo "max_usb_current=1" | sudo tee -a /boot/firmware/config.txt
fi

echo "=== 2. ELIMINANDO BLOATWARE (PARA ACELERAR LA ACTUALIZACIÓN) ==="
# Al borrar esto ANTES de actualizar, nos ahorramos descargar e instalar 
# horas de software gráfico que no usas en la Pi 2.
sudo apt-get remove --purge -y libreoffice* chromium* vlc* xserver-xorg* x11* || true
sudo apt-get autoremove -y || true
sudo apt-get clean

echo "=== 3. PREPARANDO REPOSITORIOS PARA TRIXIE ==="
sudo sed -i 's/bookworm/trixie/g' /etc/apt/sources.list
sudo sed -i 's/bookworm/trixie/g' /etc/apt/sources.list.d/*.list || true
# Añadir claves GPG que faltan en Trixie
sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 6ED0E7B82643E131 78DBA3BC47EF2265 762F67A0B2C39DE4 BDE6D2B9216EC7A8 8E9F831205B4BA95 || true
sudo apt-get update

echo "=== 4. LANZANDO ACTUALIZACIÓN SEGURA ==="
echo "La actualización a Trixie va a comenzar en segundo plano."
echo "Aunque pierdas el acceso SSH, la placa seguirá actualizándose sola."
echo "Espera 45 minutos y luego reinicia la placa quitando el cable de corriente."

# Lanzamos la actualización bloqueando las ventanas interactivas y 
# aislándola con nohup para que no muera si se corta el SSH.
export DEBIAN_FRONTEND=noninteractive
nohup sudo -E apt-get -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" full-upgrade -yq > /var/log/trixie_upgrade.log 2>&1 &

echo "¡PROCESO LANZADO CON ÉXITO!"
