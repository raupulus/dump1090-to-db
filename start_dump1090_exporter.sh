#!/bin/bash

count=0

while [[ true ]]
do
    php dump1090_exporter.php

    count=$((count + 1))

    ## Cada 3 iteraciones sube a la api todo lo que haya
    if [[ $count = 3 ]]; then
        echo 'Subiendo a la api'

        count=0

        php upload_data_to_api.php
    else
        sleep 10
    fi
done
