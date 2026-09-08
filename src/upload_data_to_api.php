<?php

namespace App;

use App\Helpers\HardwareInfo;
use App\Helpers\Log;
use App\Models\Dbconnection;
use Exception;
use Symfony\Component\Dotenv\Dotenv;
use function count;
use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function defined;
use function filter_var;
use function is_numeric;
use function json_decode;
use function json_encode;
use function max;
use function min;
use function print_r;
use function round;
use function trim;
use const API_TOKEN;
use const API_URL;
use const BATCH_SIZE;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const DB_ERROR_CONNECTION;
use const DEBUG;
use const DEVICE_ID;
use const FILTER_VALIDATE_BOOLEAN;

require_once __DIR__ . '/../vendor/autoload.php';

## Overwrite existing env variables
$dotenv = new Dotenv();
$dotenv->overload(__DIR__ . '/../.env');

## Environment vars
define('DEBUG', isset($_ENV['DEBUG']) ? filter_var($_ENV['DEBUG'], FILTER_VALIDATE_BOOLEAN) : false);

## DB
define('DB_CONNECTION', isset($_ENV['DB_CONNECTION']) ? $_ENV['DB_CONNECTION'] : 'pgsql');
define('DB_HOST', isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : '127.0.0.1');
define('DB_PORT', isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '5432');
define('DB_DATABASE', isset($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : 'dump1090');
define('DB_USERNAME', isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'pi');
define('DB_PASSWORD', isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : '');

## Messages
define('DB_ERROR_CONNECTION', 'Error al conectar con la base de datos');

## Api V2
define('API_URL', isset($_ENV['API_URL']) ? $_ENV['API_URL'] : 'https://api.fryntiz.dev/api/v2/airflight/aircrafts/batch');
define('API_TOKEN', isset($_ENV['API_TOKEN']) ? $_ENV['API_TOKEN'] : '');
define('DEVICE_ID', isset($_ENV['DEVICE_ID']) && $_ENV['DEVICE_ID'] !== '' ? (int)$_ENV['DEVICE_ID'] : null);
define('BATCH_SIZE', isset($_ENV['BATCH_SIZE']) ? min(500, max(1, (int)$_ENV['BATCH_SIZE'])) : 100);

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

function getDbConnection(): ?Dbconnection
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

/**
 * Obtiene los últimos reportes y los estructura estrictamente según el contrato API V2.
 *
 * @param int $limit
 * @return array{items: array<int, array<string, mixed>>, ids: array<int, int>}
 */
function getDbData(int $limit = 100): array
{
    $db = getDbConnection();
    $datas = $db ? $db->getLastsAirflight($limit) : null;

    $items = [];
    $ids = [];

    if ($datas) {
        foreach ($datas as $row) {
            $ids[] = (int) $row['id'];

            // Saneamiento de campos según contrato StoreBatchAirFlightRequest
            $icao = trim((string) ($row['icao'] ?? ''));
            if ($icao === '') {
                continue;
            }

            $flight = isset($row['flight']) ? trim((string) $row['flight']) : null;
            $squawk = isset($row['squawk']) ? trim((string) $row['squawk']) : null;

            $lat = (isset($row['lat']) && is_numeric($row['lat'])) ? (float) $row['lat'] : null;
            if ($lat !== null && ($lat < -90 || $lat > 90)) {
                $lat = null;
            }

            $lon = (isset($row['lon']) && is_numeric($row['lon'])) ? (float) $row['lon'] : null;
            if ($lon !== null && ($lon < -180 || $lon > 180)) {
                $lon = null;
            }

            $altitude = (isset($row['altitude']) && is_numeric($row['altitude'])) ? round((float) $row['altitude'], 1) : null;
            if ($altitude !== null && ($altitude < 0 || $altitude > 60000)) {
                $altitude = null;
            }

            $speed = (isset($row['speed']) && is_numeric($row['speed'])) ? round((float) $row['speed'], 1) : null;
            if ($speed !== null && ($speed < 0 || $speed > 1000)) {
                $speed = null;
            }

            $track = (isset($row['track']) && is_numeric($row['track'])) ? (int) round((float) $row['track']) : null;
            if ($track !== null && ($track < 0 || $track > 360)) {
                $track = null;
            }

            $messages = (isset($row['messages']) && is_numeric($row['messages'])) ? max(0, (int) $row['messages']) : null;
            $vertRate = (isset($row['vert_rate']) && is_numeric($row['vert_rate'])) ? round((float) $row['vert_rate'], 1) : null;
            $rssi = (isset($row['rssi']) && is_numeric($row['rssi'])) ? round((float) $row['rssi'], 1) : null;

            $item = [
                'icao' => $icao,
                'flight' => ($flight !== '') ? $flight : null,
                'squawk' => ($squawk !== '') ? $squawk : null,
                'lat' => $lat,
                'lon' => $lon,
                'altitude' => $altitude,
                'vert_rate' => $vertRate,
                'speed' => $speed,
                'track' => $track,
                'seen' => null,
                'seen_pos' => null,
                'messages' => $messages,
                'rssi' => $rssi,
            ];

            $items[] = $item;
        }
    }

    return [
        'items' => $items,
        'ids' => $ids,
    ];
}

/**
 * Elimina de la base de datos local los reportes procesados.
 *
 * @param array<int, int> $ids
 * @return mixed
 */
function deleteDbData(array $ids)
{
    if (empty($ids)) {
        return null;
    }

    $db = getDbConnection();
    return $db ? $db->deleteAirflight($ids) : null;
}

/**
 * Realiza la llamada HTTP POST a la API V2 con el lote de aviones y estado de hardware.
 *
 * @param array $aircraftList
 * @return bool
 */
function uploadToApi(array $aircraftList): bool
{
    if (empty($aircraftList)) {
        return false;
    }

    $url = API_URL;
    $token = API_TOKEN;
    $deviceId = DEVICE_ID;

    $db = getDbConnection();
    $bufferCount = $db ? $db->countPendingReports() : null;

    // Recolecta métricas de hardware de bajo coste
    $hardwareInfo = HardwareInfo::getStatus($bufferCount);

    $payload = [
        'hardware_device_id' => $deviceId ?: null,
        'data' => $aircraftList,
        'hardware_device_info' => $hardwareInfo,
    ];

    $jsonPayload = json_encode($payload);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 25);

    $resp = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);

    curl_close($curl);

    if ($curlError) {
        if (DEBUG) {
            Log::error("Error cURL al conectar con API: $curlError");
        }
        return false;
    }

    $respData = json_decode($resp, true);

    $isSuccess = ($httpCode >= 200 && $httpCode < 300 && isset($respData['success']) && $respData['success'] === true);

    if (DEBUG || !$isSuccess) {
        echo "\n--- RESPUESTA API V2 (HTTP $httpCode) ---\n";
        if (isset($respData['message'])) {
            echo "Mensaje: " . $respData['message'] . "\n";
        }
        if (isset($respData['data']['count'])) {
            echo "Aviones registrados en lote: " . $respData['data']['count'] . "\n";
        }
        if (isset($respData['errors'])) {
            echo "Errores de validación:\n";
            print_r($respData['errors']);
        }
        if (!$respData) {
            echo "Respuesta cruda: $resp\n";
        }
    }

    return $isSuccess;
}

function start()
{
    if (DEBUG) {
        Log::info('Iniciando subida de api en upload_data_to_api.php (API V2)');
    }

    ## Obtengo últimos reportes formateados según V2
    $batch = getDbData(BATCH_SIZE);
    $items = $batch['items'];
    $ids = $batch['ids'];

    $count = count($items);

    if ($count === 0) {
        if (DEBUG) {
            echo "No hay vuelos pendientes en la base de datos.\n";
        }
        return;
    }

    if (DEBUG) {
        echo "Procesando lote de $count reportes...\n";
    }

    ## Intento subir el lote a la API
    $uploaded = uploadToApi($items);

    if (DEBUG || !$uploaded) {
        echo "Resultado de subida API: " . ($uploaded ? "ÉXITO" : "FALLO") . "\n";
    }

    ## Si se subieron correctamente, los elimino del buffer local en RAM
    if ($uploaded && !empty($ids)) {
        deleteDbData($ids);
        if (DEBUG) {
            Log::info("Purgados $count registros de la base de datos local tras subida exitosa.");
        }
    }

    if (DEBUG) {
        Log::info('Finalizada subida de api en upload_data_to_api.php');
    }
}

if (defined('DB_PARAMS') && DB_PARAMS) {
    start();
} else if (DEBUG) {
    Log::error('No hay datos de conexión con la base de datos');
}
