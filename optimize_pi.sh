#!/bin/bash
echo "1. Desactivando Swap..."
sudo dphys-swapfile swapoff || true
sudo dphys-swapfile uninstall || true
sudo systemctl disable dphys-swapfile || true
sudo apt-get purge -y dphys-swapfile || true
sudo rm -f /var/swap

echo "2. Pasando Logs a RAM..."
if ! grep -q "/var/log" /etc/fstab; then
    echo "tmpfs /var/log tmpfs defaults,noatime,nosuid,mode=0755,size=50m 0 0" | sudo tee -a /etc/fstab
fi

echo "3. Optimizando PostgreSQL..."
CONF=$(find /etc/postgresql -name postgresql.conf | head -n 1)
if [ -n "$CONF" ]; then
    sudo sed -i 's/^#fsync = on/fsync = off/' "$CONF"
    sudo sed -i 's/^#synchronous_commit = on/synchronous_commit = off/' "$CONF"
    sudo sed -i 's/^#full_page_writes = on/full_page_writes = off/' "$CONF"
    sudo sed -i "s|^#stats_temp_directory = '.*'|stats_temp_directory = '/run/postgresql'|" "$CONF"
fi

echo "4. Desactivando servicios innecesarios..."
sudo systemctl disable bluetooth cups avahi-daemon || true

echo "5. Activando máxima energía USB para el SDR..."
if ! grep -q "max_usb_current=1" /boot/firmware/config.txt; then
    echo "max_usb_current=1" | sudo tee -a /boot/firmware/config.txt
fi

echo "¡Optimización completada! Por favor, reinicia la Raspberry."
