<?php

namespace App;
use App\Helpers\Log;
use App\Models\Airflight;
use App\Models\Dbconnection;
use function define;
use function file_exists;
use Symfony\Component\Dotenv\Dotenv;

require 'vendor/autoload.php';



// JSON

/*

{ "now" : 1615755572.8,
  "messages" : 102260,
  "aircraft" : [
    {"hex":"346353","alt_baro":40800,"category":"A3","version":2,"sil_type":"perhour","mlat":[],"tisb":[],"messages":219,"seen":3.8,"rssi":-28.2},
    {"hex":"34550a","sil_type":"unknown","mlat":[],"tisb":[],"messages":153,"seen":159.6,"rssi":-26.8},
    {"hex":"4ca9c2","flight":"RYR8RR  ","alt_baro":9650,"alt_geom":10275,"gs":287.8,"ias":251,"tas":292,"mach":0.452,"track":6.4,"track_rate":0.00,"roll":0.4,"mag_heading":9.7,"baro_rate":-1408,"geom_rate":-1408,"category":"A3","nav_qnh":1023.2,"nav_altitude_mcp":2016,"nav_heading":9.1,"lat":36.965130,"lon":-6.141596,"nic":8,"rc":186,"seen_pos":60.7,"version":2,"nic_baro":1,"nac_p":8,"nac_v":1,"sil":3,"sil_type":"perhour","gva":1,"sda":2,"mlat":[],"tisb":[],"messages":2334,"seen":54.6,"rssi":-24.8},
    {"hex":"346183","category":"A3","version":2,"sil_type":"perhour","mlat":[],"tisb":[],"messages":435,"seen":75.4,"rssi":-27.1}
  ]
}

 */

## Environment vars

define('DEBUG', true);
//define('PATH_TO_AIRCRAFT_JSON', '/run/dump1090-fa/aircraft.json');
define('PATH_TO_AIRCRAFT_JSON', 'test.json');

## Messages

define('JSON_FILE_EXIST', 'El archivo JSON existe');
define('JSON_FILE_NOT_EXIST', 'No existe el archivo JSON');
define('AIRCRAFT_AVAILABLE', 'Hay registro de vuelos');
define('AIRCRAFT_NOT_AVAILABLE', 'No hay registro de vuelos');

## DB

define('DB_CONNECTION', 'psql');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '5432');
define('DB_DATABASE', 'dump1090');
define('DB_USERNAME', 'dbuser');
define('DB_PASSWORD', 'dbpassword');

/**
 * Comprueba si existe el archivo json.
 *
 * @return bool
 */
function jsonExist()
{
    return file_exists(PATH_TO_AIRCRAFT_JSON);
}

function importJson()
{
    $fileContent = file_get_contents(PATH_TO_AIRCRAFT_JSON);

    if (!$fileContent) {
        return null;
    }

    $json = json_decode($fileContent, true);

    return  $json;
}

function export()
{
    if (jsonExist()) {
        Log::success(JSON_FILE_EXIST);

        $jsonData = importJson();
    } else {
        Log::error(JSON_FILE_NOT_EXIST);

        return false;
    }

    if ($jsonData && isset($jsonData['aircraft'])) {
        Log::info(AIRCRAFT_AVAILABLE);

        $now = isset($jsonData['now']) ? $jsonData['now'] : null;

        if ($now) {
            $airflight = new Airflight($jsonData, $now);
        } else {
            $airflight = new Airflight($jsonData);
        }

        //var_dump($airflight->aircraft);

        $db = new Dbconnection([
            'DB_SGBD' => DB_CONNECTION,
            'DB_HOST' => DB_HOST,
            'DB_PORT' => DB_PORT,
            'DB_NAME' => DB_DATABASE,
            'DB_USER' => DB_USERNAME,
            'DB_PASSWORD' => DB_PASSWORD,
        ]);
    } else {
        Log::info(AIRCRAFT_NOT_AVAILABLE);
        return false;
    }
}

export();
