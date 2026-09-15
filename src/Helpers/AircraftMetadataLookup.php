<?php

namespace App\Helpers;

use Throwable;
use function file_exists;
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
 * Resuelve la matrícula (registration), el tipo ICAO de aeronave (aircraft_type),
 * la categoría de turbulencia de estela (wtc) y la descripción de fuselaje (desc)
 * a partir de la base estática local (/usr/share/skyaware/html/db/).
 *
 * @package App\Helpers
 */
class AircraftMetadataLookup
{
    /**
     * Caché en memoria de ficheros JSON de ICAO decodificados durante la ejecución.
     *
     * @var array<string, array>
     */
    private static array $fileCache = [];

    /**
     * Caché en memoria del diccionario de tipos ICAO (icao_aircraft_types.json).
     *
     * @var array<string, array>|null
     */
    private static ?array $typesCache = null;

    /**
     * Ruta base por defecto de los ficheros de metadatos.
     *
     * @var string
     */
    private const DEFAULT_DB_PATH = '/usr/share/skyaware/html/db';

    /**
     * Resuelve los metadatos de una aeronave por su código ICAO.
     *
     * @param string|null $icao Código hexadecimal ICAO de 24 bits (ej. '4ca61f').
     * @return array{registration: ?string, aircraft_type: ?string, wtc: ?string, desc: ?string}
     */
    public static function lookup(?string $icao): array
    {
        $default = [
            'registration' => null,
            'aircraft_type' => null,
            'wtc' => null,
            'desc' => null,
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

                    $typeData = self::getAdditionalTypeData($type, $basePath);

                    return [
                        'registration' => $reg,
                        'aircraft_type' => $type,
                        'wtc' => $typeData['wtc'],
                        'desc' => $typeData['desc'],
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
     * Obtiene la categoría de estela (wtc) y descripción de fuselaje (desc) del tipo ICAO.
     *
     * @param string|null $type
     * @param string $basePath
     * @return array{wtc: ?string, desc: ?string}
     */
    private static function getAdditionalTypeData(?string $type, string $basePath): array
    {
        $def = ['wtc' => null, 'desc' => null];
        if ($type === null || $type === '') {
            return $def;
        }

        if (self::$typesCache === null) {
            $file = $basePath . '/aircraft_types/icao_aircraft_types.json';
            if (is_readable($file)) {
                $content = file_get_contents($file);
                $decoded = $content !== false ? json_decode($content, true) : null;
                self::$typesCache = is_array($decoded) ? $decoded : [];
            } else {
                self::$typesCache = [];
            }
        }

        $upper = strtoupper(trim($type));
        if (isset(self::$typesCache[$upper]) && is_array(self::$typesCache[$upper])) {
            $entry = self::$typesCache[$upper];
            $wtc = isset($entry['wtc']) && trim((string) $entry['wtc']) !== ''
                ? trim((string) $entry['wtc'])
                : null;
            $desc = isset($entry['desc']) && trim((string) $entry['desc']) !== ''
                ? trim((string) $entry['desc'])
                : null;
            return ['wtc' => $wtc, 'desc' => $desc];
        }

        return $def;
    }

    /**
     * Limpia la caché.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$fileCache = [];
        self::$typesCache = null;
    }
}
