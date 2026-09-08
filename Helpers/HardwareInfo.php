<?php

namespace App\Helpers;

/**
 * Class HardwareInfo
 *
 * Recolecta métricas del sistema (CPU, RAM, Disco, Temperatura, Voltaje, etc.)
 * con el mínimo coste de recursos posible para el payload `hardware_device_info`.
 *
 * @package App\Helpers
 */
class HardwareInfo
{
    /**
     * Obtiene el estado del hardware formateado según el contrato de la API V2.
     *
     * @param int|null $bufferCount Número opcional de registros en buffer local.
     * @return array
     */
    public static function getStatus(?int $bufferCount = null): array
    {
        $temp = self::getTemperature();
        $volt = self::getVoltage();
        $cpu = self::getCpuUsage();
        $disk = self::getDiskUsage();
        $ram = self::getRamUsage();
        $uptime = self::getUptime();
        $ipLocal = self::getLocalIp();
        $extra = self::getExtraInfo($bufferCount);

        return [
            'temp' => $temp,
            'voltage' => $volt,
            'battery_level' => null,
            'cpu' => $cpu,
            'disk' => $disk,
            'ram' => $ram,
            'uptime' => $uptime,
            'ip_local' => $ipLocal,
            'extra' => !empty($extra) ? $extra : null,
        ];
    }

    /**
     * Temperatura en grados Celsius (p. ej. 48.5) desde /sys/class/thermal.
     *
     * @return float|null
     */
    public static function getTemperature(): ?float
    {
        $path = '/sys/class/thermal/thermal_zone0/temp';
        if (is_readable($path)) {
            $raw = trim((string) @file_get_contents($path));
            if (is_numeric($raw)) {
                return round((float) $raw / 1000.0, 1);
            }
        }

        return null;
    }

    /**
     * Voltaje del SoC en voltios (p. ej. 1.20) vía vcgencmd.
     *
     * @return float|null
     */
    public static function getVoltage(): ?float
    {
        $raw = @shell_exec('vcgencmd measure_volts core 2>/dev/null');
        if ($raw && preg_match('/volt=([0-9.]+)V/', $raw, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    /**
     * Porcentaje de uso de CPU (0 a 100).
     *
     * @return float|null
     */
    public static function getCpuUsage(): ?float
    {
        $load = sys_getloadavg();
        if (is_array($load) && isset($load[0])) {
            $cores = self::getCpuCores();
            $pct = round(($load[0] / $cores) * 100.0, 1);
            return min(100.0, max(0.0, $pct));
        }

        return null;
    }

    /**
     * Porcentaje de disco raíz ocupado (0 a 100).
     *
     * @return float|null
     */
    public static function getDiskUsage(): ?float
    {
        $total = @disk_total_space('/');
        $free = @disk_free_space('/');

        if ($total !== false && $free !== false && $total > 0) {
            return round((($total - $free) / $total) * 100.0, 1);
        }

        return null;
    }

    /**
     * Porcentaje de memoria RAM ocupada (0 a 100) leyendo /proc/meminfo.
     *
     * @return float|null
     */
    public static function getRamUsage(): ?float
    {
        $mem = self::getMeminfo();
        if (isset($mem['MemTotal']) && $mem['MemTotal'] > 0) {
            $total = $mem['MemTotal'];
            $available = $mem['MemAvailable'] ?? ($mem['MemFree'] ?? 0);
            return round((($total - $available) / $total) * 100.0, 1);
        }

        return null;
    }

    /**
     * Segundos de uptime del sistema.
     *
     * @return int|null
     */
    public static function getUptime(): ?int
    {
        $path = '/proc/uptime';
        if (is_readable($path)) {
            $parts = explode(' ', trim((string) @file_get_contents($path)));
            if (isset($parts[0]) && is_numeric($parts[0])) {
                return (int) $parts[0];
            }
        }

        return null;
    }

    /**
     * Dirección IP local primaria.
     *
     * @return string|null
     */
    public static function getLocalIp(): ?string
    {
        $output = @shell_exec('hostname -I 2>/dev/null');
        if ($output) {
            $ips = preg_split('/\s+/', trim($output));
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Métricas adicionales simples para el campo `extra`.
     *
     * @param int|null $bufferCount
     * @return array
     */
    public static function getExtraInfo(?int $bufferCount = null): array
    {
        $extra = [];

        // Estado de Throttling y subtensión de silicio
        $throttledRaw = @shell_exec('vcgencmd get_throttled 2>/dev/null');
        if ($throttledRaw && preg_match('/throttled=(0x[0-9a-fA-F]+)/', $throttledRaw, $m)) {
            $extra['throttled'] = $m[1];
            $extra['undervoltage'] = (bool) (hexdec($m[1]) & 0x1);
        }

        // Cargas medias de 1m, 5m, 15m
        $load = sys_getloadavg();
        if (is_array($load) && count($load) >= 3) {
            $extra['load_1m'] = round($load[0], 2);
            $extra['load_5m'] = round($load[1], 2);
            $extra['load_15m'] = round($load[2], 2);
        }

        // Métricas de RAM en MB
        $mem = self::getMeminfo();
        if (isset($mem['MemTotal'])) {
            $avail = $mem['MemAvailable'] ?? ($mem['MemFree'] ?? 0);
            $extra['ram_free_mb'] = round($avail / 1024.0, 1);
            $extra['ram_total_mb'] = round($mem['MemTotal'] / 1024.0, 1);
        }

        // Espacio libre en disco en GB
        $freeDisk = @disk_free_space('/');
        if ($freeDisk !== false) {
            $extra['disk_free_gb'] = round($freeDisk / 1024.0 / 1024.0 / 1024.0, 2);
        }

        // Conteo de registros pendientes en la DB local
        if ($bufferCount !== null) {
            $extra['buffer_reports'] = $bufferCount;
        }

        return $extra;
    }

    /**
     * Parsea /proc/meminfo en un array asociativo.
     *
     * @return array<string, int>
     */
    private static function getMeminfo(): array
    {
        $mem = [];
        $path = '/proc/meminfo';
        if (is_readable($path)) {
            $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
                        $mem[$m[1]] = (int) $m[2];
                    }
                }
            }
        }

        return $mem;
    }

    /**
     * Detecta el número de cores de la CPU.
     *
     * @return int
     */
    private static function getCpuCores(): int
    {
        $path = '/proc/cpuinfo';
        if (is_readable($path)) {
            $content = (string) @file_get_contents($path);
            $cores = preg_match_all('/^processor\s*:\s*\d+/m', $content);
            if ($cores > 0) {
                return $cores;
            }
        }

        return 4;
    }
}
