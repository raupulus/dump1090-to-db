<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use function array_combine;
use function array_fill;
use function array_key_exists;
use function array_keys;
use function atan2;
use function cos;
use function count;
use function deg2rad;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function json_encode;
use function sin;
use function sqrt;
use function trim;
use function var_dump;

/**
 * Class Airflight
 */
class Airflight
{
    /**
     * Almacena la fecha en la que se construye este modelo.
     *
     * @var \Carbon\Carbon
     */
    private $startAt;

    /**
     * Atributos utilizados de los recibidos en el json.
     * Cada atributo es la clave que se asocia a un array de validaciones con
     * el nombre de cada función para validarlas en el mismo orden.
     */
    public $attributes = [
        'hex' => [],
        'category' => [],
        'squawk' => [],
        'flight' => [],
        'lat' => [],
        'lon' => [],
        'nucp' => [],
        'nic' => [],
        'rc' => [],
        'alt_baro' => ['feetToMeters'],
        'alt_geom' => ['feetToMeters'],
        'baro_rate' => [],
        'geom_rate' => [],
        'altitude' => ['feetToMeters'],
        'vert_rate' => ['feetToMeters'],
        'track' => [],
        'track_rate' => [],
        'speed' => ['knotsToMeters'],
        'version' => [],
        'sil_type' => [],
        'mlat' => [],
        'tisb' => [],
        'seen' => ['timestampFromLastSeconds'],
        'seen_pos' => ['timestampFromLastSeconds'],
        'rssi' => [],
        'gs' => [],
        'ias' => [],
        'tas' => [],
        'mach' => [],
        'nac_p' => [],
        'nac_v' => [],
        'sil' => [],
        'roll' => [],
        'mag_heading' => [],
        'emergency' => [],
        'nav_qnh' => [],
        'nav_altitude_mcp' => [],
        'nic_baro' => [],
        'gva' => [],
        'sda' => [],
    ];

    /*
     * Almacena todos los vuelos detectados una vez se ha limpiado.
     */
    public $aircraft = [];

    /**
     * Airflight constructor.
     *
     * @param array $datas Json decoded data from /run/dump1090-fa/aircraft.json
     * @param null  $startAt Read seconds from unix time.
     */
    public function __construct(Array $datas = [], $startAt = null)
    {
        if ($startAt) {
            try {
                $startAt = Carbon::createFromTimestamp($startAt);
            } catch (Exception $e) {
                $startAt = null;
            }
        }

        $this->startAt = $startAt ?? Carbon::now();

        ## Recorre todos los registros de vuelo.
        foreach ($datas['aircraft'] as $aircraft) {

            ## Creo un array con todos los atributos en valor null
            $data = array_combine(array_keys($this->attributes), array_fill(0, count($this->attributes), null));

            ## Recorro todos los datos para el registro actual.
            foreach ($aircraft as $key => $attribute) {

                ## Compruebo el atributo actual si quiero almacenarlo.
                if (array_key_exists($key, $this->attributes)) {
                    $value = $attribute ?? null;

                    if ($value) {
                        ## Aplico saneados a cada atributo si lo tuviera.
                        foreach ($this->attributes[$key] as $validation) {
                            $value = $this->{$validation}($value);
                        }

                        ## Si algo llega como array, lo paso a json.
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }

                        if (is_string($value)) {
                            $value = trim($value);
                        }
                    }

                    $data[$key] = $value;
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
     * @param numeric $seconds Segundos a restar.
     *
     * @return string
     */
    private function timestampFromLastSeconds($seconds)
    {
        $now = (clone($this->startAt));
        $last = (clone($now))->subSeconds($seconds);

        return $last->format('Y-m-d H:i:s');
    }

    /**
     * Convierte de nudos a metros.
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

    /**
     * Distancia estimada entre el vuelo y el receptor.
     *
     * @param $lat_from
     * @param $lon_from
     * @param $lat_to
     * @param $lon_to
     * @param $earth_radius
     *
     * @return float|int
     */
    function distanceFromReceptor($lat_from, $lon_from, $lat_to, $lon_to, $earth_radius)
    {
        $delta_lat = deg2rad($lat_to - $lat_from);
        $delta_lon = deg2rad($lon_to - $lon_from);

        $a = sin($delta_lat / 2) * sin($delta_lat / 2) + cos(deg2rad($lat_from)) *
             cos(deg2rad($lat_to)) * sin($delta_lon / 2) * sin($delta_lon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth_radius * $c;
    }
}
