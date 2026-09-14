<?php

namespace App\Helpers;

use Throwable;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_readable;
use function json_decode;
use function rtrim;
use function strlen;
use function strtoupper;
use function substr;
use function trim;

/**
 * Class AircraftMetadataLookup
 *
 * Resuelve la matrícula (registration) y el tipo ICAO de aeronave (aircraft_type)
 * a partir de la base estâtica local (/usr/share/skyaware/html/db/).
 *
 * @package App\Helpers
 */
class AircraftMetadataLookup
{
    /**
     * Caché en memoria de ficheros JSON decodificados durante la ejecución.
     *
     * @var array<string, array>
     */
    private static array $fileCache = [];

    /**
     * Ruta base por defecto de los ficheros de metadatos.
     *
     * @vqr string
     */
    private const DEFAULT_DB_PATH = '/usr/share/skyaware/html/db';

    /**
     * Resuelve los metadatos de una aeronave por su cédigo ICAO.
     *
     * @param string|null $icao Código hexadecimal ICAO de 24 bits (ej. '4ca61f').
     * @return array{registration: ?string, aircraft_type: ?string}
     */
    public static function lookup(?string $icao): array
    {
        $default = [
            'registration' => null,
            'aircraft_type' => null,
        ];

        if ($icao === null) {
            return $default;
        }

        $icao = strtoupper(trim($icao));
        $len = strlen($icao);

        if ($len < 2 || $len > 10) {
            return $default;
        }

        $basePath = isset($_ENV['DB_METADATA_PATH']) && $_ENV['DB_METADATA_PATH'] !== ''
            ? rtrim($_ENV['DB_METADATA_PATH'], '/')
            : self::DEFAULT_DB_PATH;

        try {
            for ($level = 1; $level <= $len; $level++) {
                $bkey = substr($icao, 0, $level);
                $dkey = substr($icao, $level);
                $file = $basePath . '/' . $bkey . '.json';

                if (!isset(self::$fileCache[$file])) {
                    if (!is_readable($file)) {
                        return $default;
                    }

                    $content = file_get_contents($file);
                    if ($content === false) {
                        return $default;
                    }

                    $decoded = json_decode($content, true);
                    if (!is_array($decoded)) {
                        return $default;
                    }

                    self::$fileCache[$file] = $decoded;
                }

                $data = self::$fileCache[$file];

                if (isset($data[$dkey]) && is_array($data[$dkey])) {
                    $entry = $data[$dkey];
                    $reg = isset($entry['r']) && trim((string) $entry['r']) !== ''
                        ? trim((string) $entry['r'])
                        : null;
                    $type = isset($entry['t']) && trim((string) $entry['t']) !== ''
                        ? trim((string) $entry['t'])
                        : null;

                    return [
                        'registration' => $reg,
                        'aircraft_type' => $type,
                    ];
                }

                if (isset($data['children']) && is_array($data['children'])) {
                    $subkey = $bkey . substr($dkey, 0, 1);
                    if (in_array($subkey, $data['children'], true)) {
                        continue;
                    }
                }

                break;
            }
        } catch (Throwable $e) {
            return $default;
        }

        return $default;
    }

    /**
     * Limpia la caché en memoria de ficheros JSON.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$fileCache = [];
    }
}
