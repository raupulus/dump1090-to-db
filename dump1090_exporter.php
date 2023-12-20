<?php

namespace App;
use App\Helpers\Log;
use App\Models\Airflight;
use App\Models\Dbconnection;
use Exception;
use function count;
use function define;
use function file_exists;
use Symfony\Component\Dotenv\Dotenv;

require 'vendor/autoload.php';

// JSON EXAMPLE

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

## Overwrite existing env variables
$dotenv = new Dotenv();
$dotenv->overload(__DIR__.'/.env');

## Environment vars

define('DEBUG', isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] : false);
define('PATH_TO_AIRCRAFT_JSON', isset($_ENV['PATH_TO_AIRCRAFT_JSON']) ? $_ENV['PATH_TO_AIRCRAFT_JSON'] : '/run/dump1090-fa/aircraft.json');

## Messages

define('JSON_FILE_EXIST', 'El archivo JSON existe');
define('JSON_FILE_NOT_EXIST', 'No existe el archivo JSON');
define('AIRCRAFT_AVAILABLE', 'Hay registro de vuelos');
define('AIRCRAFT_NOT_AVAILABLE', 'No hay registro de vuelos');
define('DB_ERROR_CONNECTION', 'Error al conectar con la base de datos');

## DB

define('DB_CONNECTION', isset($_ENV['DB_CONNECTION']) ? $_ENV['DB_CONNECTION'] : 'psql');
define('DB_HOST', isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : '127.0.0.1');
define('DB_PORT', isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '5432');
define('DB_DATABASE', isset($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : 'dump1090');
define('DB_USERNAME', isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'dbuser');
define('DB_PASSWORD', isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : '');

/**
 * Comprueba si existe el archivo json.
 *
 * @return bool
 */
function jsonExist()
{
    return file_exists(PATH_TO_AIRCRAFT_JSON);
}

/**
 * Import json data from dump1090 file.
 *
 * @return mixed|null
 */
function importJson()
{
    $fileContent = file_get_contents(PATH_TO_AIRCRAFT_JSON);

    if (!$fileContent) {
        return null;
    }

    $json = json_decode($fileContent, true);

    return  $json;
}

/**
 * Main function to export data from dump1090 to db.
 *
 * @return false
 */
function export()
{
    ## Check json exist.
    if (jsonExist()) {
        if (DEBUG) {
            Log::success(\JSON_FILE_EXIST);
        }

        $jsonData = importJson();
    } else {
        if (DEBUG) {
            Log::error(JSON_FILE_NOT_EXIST);
        }

        return false;
    }

    ## Check json data.
    if ($jsonData && isset($jsonData['aircraft'])) {
        if (DEBUG) {
            Log::info(AIRCRAFT_AVAILABLE);
        }

        $now = isset($jsonData['now']) ? $jsonData['now'] : null;

        $airflight = new Airflight($jsonData, $now, DEBUG);

        $aircrafts = $airflight->aircraft;

        if ($aircrafts && count($aircrafts)) {
            try {
                $db = new Dbconnection([
                    'DB_SGBD' => DB_CONNECTION,
                    'DB_HOST' => DB_HOST,
                    'DB_PORT' => DB_PORT,
                    'DB_NAME' => DB_DATABASE,
                    'DB_USER' => DB_USERNAME,
                    'DB_PASSWORD' => DB_PASSWORD,
                ]);
            } catch (Exception $e) {
                if (DEBUG) {
                    Log::error(DB_ERROR_CONNECTION);
                }

                return false;
            }

            $db->saveAirflight($airflight->aircraft);
        }
    } else {
        if (DEBUG) {
            Log::info(AIRCRAFT_NOT_AVAILABLE);
        }

        return false;
    }
}

export();
