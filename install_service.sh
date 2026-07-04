#!/bin/bash
#
# Instala y arranca dump1090-to-db como servicio systemd.
# Uso: sudo ./install_service.sh

set -euo pipefail

if [[ $EUID -ne 0 ]]; then
    echo "Este script debe ejecutarse como root: sudo ./install_service.sh"
    exit 1
fi

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_NAME="dump1090-to-db"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
TEMPLATE_FILE="${PROJECT_DIR}/systemd/${SERVICE_NAME}.service.template"

# Usuario que ejecutará el servicio: el que invocó sudo, o 'pi' si no se detecta.
SERVICE_USER="${SUDO_USER:-pi}"

if [[ ! -f "$TEMPLATE_FILE" ]]; then
    echo "No se encuentra la plantilla del servicio en: $TEMPLATE_FILE"
    exit 1
fi

## Resuelve dependencias base (BD + composer) si aún no se han instalado.
if [[ ! -d "${PROJECT_DIR}/vendor" ]]; then
    echo "No existen las dependencias de composer, ejecutando ./installer.sh ..."
    "${PROJECT_DIR}/installer.sh"
fi

## Crea el .env si no existe, a partir de la plantilla.
if [[ ! -f "${PROJECT_DIR}/.env" ]]; then
    echo "AVISO: no existe .env, se copia desde .env.example."
    echo "       Edita ${PROJECT_DIR}/.env con tus credenciales antes de confiar en el servicio."
    cp "${PROJECT_DIR}/.env.example" "${PROJECT_DIR}/.env"
fi

chmod +x "${PROJECT_DIR}/start_dump1090_exporter.sh"

echo "Instalando servicio systemd '${SERVICE_NAME}'"
echo " - Directorio del proyecto: ${PROJECT_DIR}"
echo " - Usuario del servicio:    ${SERVICE_USER}"

sed \
    -e "s|__WORKING_DIR__|${PROJECT_DIR}|g" \
    -e "s|__SERVICE_USER__|${SERVICE_USER}|g" \
    "$TEMPLATE_FILE" > "$SERVICE_FILE"

systemctl daemon-reload
systemctl enable "${SERVICE_NAME}.service"
systemctl restart "${SERVICE_NAME}.service"

echo ""
echo "Servicio instalado y en marcha."
echo " - Estado:  systemctl status ${SERVICE_NAME}"
echo " - Logs:    journalctl -u ${SERVICE_NAME} -f"
echo " - Parar:   sudo systemctl stop ${SERVICE_NAME}"
echo " - Deshabilitar: sudo systemctl disable ${SERVICE_NAME}"
