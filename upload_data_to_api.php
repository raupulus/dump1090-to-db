<?php

namespace App;
use App\Helpers\Log;
use App\Models\Dbconnection;
use Exception;
use Symfony\Component\Dotenv\Dotenv;
use function count;
use function curl_setopt;
use function define;
use function http_build_query;
use function in_array;
use function json_encode;
use function var_dump;
use const API_TOKEN;
use const API_URL;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const DB_ERROR_CONNECTION;
use const DEBUG;

require 'vendor/autoload.php';

## Overwrite existing env variables
$dotenv = new Dotenv();
$dotenv->overload(__DIR__.'/.env');

## Environment vars

define('DEBUG', isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] : true);

## DB

define('DB_CONNECTION', isset($_ENV['DB_CONNECTION']) ? $_ENV['DB_CONNECTION'] : 'psql');
define('DB_HOST', isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : '127.0.0.1');
define('DB_PORT', isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '5432');
define('DB_DATABASE', isset($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : 'dump1090');
define('DB_USERNAME', isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'dbuser');
define('DB_PASSWORD', isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : '');

## Messages

define('DB_ERROR_CONNECTION', 'Error al conectar con la base de datos');

## Api
define('API_URL', isset($_ENV['API_URL']) ? $_ENV['API_URL'] : '');
define('API_TOKEN', isset($_ENV['API_TOKEN']) ? $_ENV['API_TOKEN'] : '');
define('DEVICE_ID', isset($_ENV['DEVICE_ID']) ? $_ENV['DEVICE_ID'] : '');

try {
    $dbParams = [
        'DB_SGBD' => DB_CONNECTION,
        'DB_HOST' => DB_HOST,
        'DB_PORT' => DB_PORT,
        'DB_NAME' => DB_DATABASE,
        'DB_USER' => DB_USERNAME,
        'DB_PASSWORD' => DB_PASSWORD,
    ];

    define('DB_PARAMS', $dbParams);
} catch (Exception $e) {
    if (DEBUG) {
        Log::error(DB_ERROR_CONNECTION);
    }

    return false;
}

function getDbConnection()
{
    try {
        return new Dbconnection(DB_PARAMS);
    } catch (Exception $e) {
        if (DEBUG) {
            Log::error(DB_ERROR_CONNECTION);
        }

        return null;
    }
}

function getDbData()
{
    $db = getDbConnection();

    $datas =  $db ? $db->getLastsAirflight(10) : null;

    $datasClean = [];
    
    if ($datas) {
        $attributesValid = ['id', 'icao', 'category', 'squawk', 'flight', 'lat',
            'lon', 'altitude', 'vert_rate', 'track', 'speed', 'seen_at', 'messages', 'rssi', 'emergency'];
        
        foreach ($datas as $data) {

            $prepare = [];

            foreach ($data as $idx => $attribute) {
                if (in_array($idx, $attributesValid)) {
                    $prepare[$idx] = $attribute;
                }
            }

            $datasClean[] = $prepare;
        }
    }

    return $datasClean;
}

function deleteDbData($datas)
{
    $db = getDbConnection();

    $ids = [];

    foreach ($datas as $data) {
        $ids[] = $data['id'];
    }

    if (count($ids)) {
        return $db ? $db->deleteAirflight($ids) : null;
    }

    return null;
}

function uploadToApi($data)
{
    $url = API_URL;
    $token = API_TOKEN;
    $deviceId = DEVICE_ID;


    $parameters = http_build_query([
        'data' => json_encode($data),
        'hardware_device_id' => $deviceId
    ]);

    //var_dump($parameters);

    $headers = [
        //'Accept: application/json',
        //'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        //'Content-length: ' . strlen($parameters),
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $resp = curl_exec($curl);

    curl_close($curl);

    echo "\n RESPUESTA API \n";
    //var_dump(curl_getinfo($curl));
    var_dump($resp);
    //var_dump($resp); die();


    //TODO: esto tiene que devolver si o si un boolean

    return $resp == '"Guardado Correctamente"';
}

function start()
{
    if (DEBUG) {
        Log::info('Iniciando subida de api en upload_data_to_api.php función uploadToApi');
    }

    ## Obtengo últimos reportes.
    $data = getDbData();

    ## Si existen reportes, intento subirlos a la api.
    $upload = $data ? uploadToApi($data) : false;

    echo "\nUpload: " . $upload . "\n";

    ## Si se subieron los reportes correctamente a la api los elimino.
    if ($upload) {
        echo "\nEliminando datos\n";

        $delete = deleteDbData($data);
    }

    try {
        if ($data) {

        }

        /*
        if ($delete && DEBUG) {
            Log::info('Eliminado correctamente');
        } else if (DEBUG) {
            Log::info('No se ha podido eliminar');
        }
        */
    } catch (Exception $e) {
        if (DEBUG) {
            Log::error('Error al intentar eliminar elementos de la DB');
            Log::error($e);
        }
    }


    if (DEBUG) {
        Log::info('Termina subida de api en upload_data_to_api.php función uploadToApi');
    }
}

if (DB_PARAMS) {
    start();
} else if (DEBUG) {
    Log::error('No hay datos de conexión con la base de datos');
}
?>
