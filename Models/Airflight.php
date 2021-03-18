<?php

namespace App\Models;

use function in_array;
use function var_dump;

/**
 * Class Airflight
 */
class Airflight
{
    public $attributes = [
        'hex',
        'alt_baro',
        'category',
        'version',
        'sil_type',
        'mlat',
        'tisb',
        'seen',
        'rssi',
        'alt_geom',
        'gs',
        'ias',
        'tas',
        'mach'
    ];

    /*
{"hex":"4ca9c2","flight":"RYR8RR  ","alt_baro":9650,"alt_geom":10275,"gs":287.8,"ias":251,"tas":292,"mach":0.452,"track":6.4,"track_rate":0.00,"roll":0.4,"mag_heading":9.7,"baro_rate":-1408,"geom_rate":-1408,"category":"A3","nav_qnh":1023.2,"nav_altitude_mcp":2016,"nav_heading":9.1,"lat":36.965130,"lon":-6.141596,"nic":8,"rc":186,"seen_pos":60.7,"version":2,"nic_baro":1,"nac_p":8,"nac_v":1,"sil":3,"sil_type":"perhour","gva":1,"sda":2,"mlat":[],"tisb":[],"messages":2334,"seen":54.6,"rssi":-24.8},
*/



    public $aircraft = [];

    public function __construct(Array $datas = [])
    {
        foreach ($datas['aircraft'] as $aircraft) {
            // filter $attributes in $array

           // var_dump($aircraft);

            $data = [];

            ## Recorro todos los registros
            foreach ($aircraft as $idx => $attribute) {

                ## Compruebo el atributo actual si quiero almacenarlo
                if (in_array($idx, $this->attributes)) {

                    switch ($attribute) {
                        case :
                    }

                    $data[$idx] = $attribute;
                }
            }

            if ($data && count($data)) {
                $this->aircraft[] = $data;
            }
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
}
