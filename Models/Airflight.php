<?php

namespace App\Models;

use Carbon\Carbon;
use function array_key_exists;
use function array_keys;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function json_encode;
use function var_dump;

/**
 * Class Airflight
 */
class Airflight
{
    private $startAt;

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
        'squawk' => [],
        'flight' => [],
        'lat' => [],
        'lon' => [],
        'nucp' => [],
        'alt_baro' => ['feetToMeters'],
        'altitude' => ['feetToMeters'],
        'vert_rate' => ['feetToMeters'],
        'track' => ['toInt'],
        'speed' => ['knotsToMeters'],
        'version' => [],
        'sil_type' => [],
        'mlat' => [],
        'tisb' => [],
        'seen' => ['toInt', 'timestampFromLastSeconds'],
        'seen_pos' => ['toInt', 'timestampFromLastSeconds'],
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
        $this->startAt = Carbon::now();

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
     * @param mixed $feets Recibe la cantidad de pies a convertir.
     *
     * @return float|null
     */
    private function feetToMeters($feets)
    {
        if (!$feets) {
            return null;
        }

        if (($feets === 'ground') || !is_numeric($feets)) {
            return 0.0;
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

    /**
     * Crea y devuelve un timestamp restando los segundos recibidos respecto
     * al momento de construirse la clase para minimizar la diferencia de
     * tiempo por la ejecución.
     *
     * @param int $seconds Segundos a restar.
     *
     * @return string
     */
    private function timestampFromLastSeconds(int $seconds)
    {
        $now = (clone($this->startAt));
        $last = (clone($now))->subSeconds($seconds);

        return $last->format('Y-m-d H:i:s');
    }

    /**
     * 
     *
     * @param mixed $kt Nudos
     *
     * @return float|null
     */
    private function knotsToMeters($kt)
    {
        if (! $kt || ! is_numeric($kt)) {
            return null;
        }

        if ($kt <= 0) {
            return 0.0;
        }

        return (float) ($kt / 1.94384);
    }
}
