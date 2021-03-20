<?php

namespace App\Models;

use function array_key_exists;
use function array_keys;
use function implode;
use function in_array;
use function is_array;
use function json_encode;
use function var_dump;

/**
 * Class Airflight
 */
class Airflight
{
    /**
     * Atributos utilizados de los recibidos en el json.
     * Cada atributo es la clave que se asocia a un array de validaciones con
     * el nombre de cada función para validarlas en el mismo orden.
     *
     * @var string[]
     */
    public $attributes = [
        'hex' => [],
        'category' => [],
        'alt_baro' => ['feetToMeters'],
        'version' => [],
        'sil_type' => [],
        'mlat' => [],
        'tisb' => [],
        'seen' => ['toInt', 'timestampFromLastSeconds'],
        'rssi' => [],
        'alt_geom' => [],
        'gs' => [],
        'ias' => [],
        'tas' => [],
        'mach' => [],
    ];

    /*
{"hex":"4ca9c2","flight":"RYR8RR  ","alt_baro":9650,"alt_geom":10275,"gs":287.8,"ias":251,"tas":292,"mach":0.452,"track":6.4,"track_rate":0.00,"roll":0.4,"mag_heading":9.7,"baro_rate":-1408,"geom_rate":-1408,"category":"A3","nav_qnh":1023.2,"nav_altitude_mcp":2016,"nav_heading":9.1,"lat":36.965130,"lon":-6.141596,"nic":8,"rc":186,"seen_pos":60.7,"version":2,"nic_baro":1,"nac_p":8,"nac_v":1,"sil":3,"sil_type":"perhour","gva":1,"sda":2,"mlat":[],"tisb":[],"messages":2334,"seen":54.6,"rssi":-24.8},
*/



    public $aircraft = [];

    public function __construct(Array $datas = [])
    {
        ## Recorre todos los registros de vuelo.
        foreach ($datas['aircraft'] as $aircraft) {
            // TODO → filter $attributes in $array

            $data = [];

            ## Recorro todos los datos para el registro actual.
            foreach ($aircraft as $key => $attribute) {

                ## Compruebo el atributo actual si quiero almacenarlo.
                if (array_key_exists($key, $this->attributes)) {

                    $value = $attribute;

                    ## Aplico saneados a cada atributo si lo tuviera.
                    foreach ($this->attributes[$key] as $validation) {
                        echo "\n $validation \n";
                        $value = $this->{$validation}($value);
                    }

                    ## Si algo llega como array, lo paso a json.
                    if (is_array($value)) {
                        //$value = implode(',', $value);
                        $value = json_encode($value);

                    }


                    $data[$key] = $value;
                }
            }

            if ($data && count($data)) {
                $this->aircraft[] = $data;
            }

            break;
        }
    }

    /**
     * Convierte de pies a metros.
     *
     * @param float $feets Recibe la cantidad de pies a convertir.
     *
     * @return float|null
     */
    private function feetToMeters(float $feets)
    {
        if (!$feets) {
            return null;
        }

        if ($feets <= 0) {
            return (float) $feets;
        }

        return (float) ($feets / 3.281);
    }

    /**
     * Recibe un valor e intentar convertirlo en entero.
     *
     * @param mixed $value Valor a convertir.
     *
     * @return int
     */
    private function toInt($value)
    {
        return (int)$value;
    }

    private function timestampFromLastSeconds(int $seconds)
    {
        return $seconds;
    }
}
