# Captura de señales para finalización limpia e instantánea en systemd
trap 'echo "Deteniendo dump1090-to-db..."; exit 0' SIGTERM SIGINT

# Cargar variables de entorno si existe el archivo
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Valores por defecto si no existen
T_INTERVAL_CHECK=${T_INTERVAL_CHECK:-10}
T_INTERVAL_UPLOAD_API=${T_INTERVAL_UPLOAD_API:-3}

count=0

while [[ true ]]
do
    timeout 20 php dump1090_exporter.php

    count=$((count + 1))

    ## Sube a la API según la configuración de iteraciones
    if [[ $count -ge $T_INTERVAL_UPLOAD_API ]]; then
        echo "Subiendo a la api"

        count=0

        timeout 30 php upload_data_to_api.php
        sleep $T_INTERVAL_CHECK
    else
        sleep $T_INTERVAL_CHECK
    fi
done

