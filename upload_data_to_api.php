<?php

namespace App;
use App\Helpers\Log;
use App\Models\Dbconnection;
use Exception;
use Symfony\Component\Dotenv\Dotenv;
use function define;
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

function getDbData()
{
    return Dbconnection::getLastAirflights(10);
}

function deleteDbData($data)
{
    return Dbconnection::deleteAirflight($data);
}

function uploadToApi()
{
    if (DEBUG) {
        Log::info('Iniciando subida de api en upload_data_to_api.php función uploadToApi');
    }

    $data = getDbData();

    // UPLOAD

    $delete = deleteDbData($data);

    try {
        if ($data) {

        }

        if ($delete && DEBUG) {
            Log::info('Eliminado correctamente');
        } else if (DEBUG) {
            Log::info('No se ha podido eliminar');
        }
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

//uploadToApi();
?>
