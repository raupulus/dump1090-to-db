<?php

namespace App\Models;

use function array_filter;
use function array_shift;
use function count;

/**
 * Class Aircraft
 *
 * Representa un vuelo.
 *
 * @package App\Models
 */
class Aircraft
{
    /**
     * Aircraft constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->setIcao($data);
        $this->setCategory($data);
        $this->setSquawk($data);
        $this->setFlight($data);
        $this->setLon($data);
        $this->setLat($data);
        $this->setAltitude($data);
        $this->setVertRate($data);
        $this->setTrack($data);
        $this->setSpeed($data);
        $this->setSeenAt($data);
        $this->setMessages($data);
        $this->setRssi($data);
        $this->setEmergency($data);
        $this->setRegistration($data);
        $this->setAircraftType($data);
        $this->setWtc($data);
        $this->setAircraftDesc($data);
        $this->setNavAltitudeMcp($data);
        $this->setNavQnh($data);
        $this->setNavHeading($data);
        $this->setMach($data);
        $this->setMagHeading($data);
        $this->setRoll($data);
        $this->setIas($data);
        $this->setTas($data);
        $this->setGeomRate($data);
        $this->setNic($data);
        $this->setRc($data);
    }

    private function setIcao($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setCategory($data)
    {
        $this->category = isset($data['category']) ? $data['category'] : null;
    }

    private function setSquawk($data)
    {
        $this->squawk = isset($data['squawk']) ? $data['squawk'] : null;
    }

    private function setFlight($data)
    {
        $this->flight = isset($data['flight']) ? $data['flight'] : null;
    }

    private function setLat($data)
    {
        $this->lat = isset($data['lat']) ? $data['lat'] : null;
    }

    private function setLon($data)
    {
        $this->lon = isset($data['lon']) ? $data['lon'] : null;
    }

    private function setAltitude($data)
    {
        $values = [
            isset($data['altitude']) ? $data['altitude'] : null,
            isset($data['alt_geom']) ? $data['alt_geom'] : null,
            isset($data['alt_baro']) ? $data['alt_baro'] : null,
        ];

        $values = array_filter($values, 'strlen');

        if (count($values)) {
            $altitude = array_shift($values);
        } else {
            $altitude = null;
        }

        $this->altitude = $altitude;
    }

    private function setVertRate($data)
    {
        $values = [
            isset($data['vert_rate']) ? $data['vert_rate'] : null,
            isset($data['baro_rate']) ? $data['baro_rate'] : null,
            isset($data['geom_rate']) ? $data['geom_rate'] : null,
        ];

        $values = array_filter($values, static function ($val) {
            return $val !== null && $val !== '';
        });

        if (count($values)) {
            $vertRate = array_shift($values);
        } else {
            $vertRate = null;
        }

        $this->vert_rate = $vertRate;
    }

    private function setTrack($data)
    {
        $this->track = isset($data['track']) ? $data['track'] : null;
    }

    private function setSpeed($data)
    {
        $values = [
            isset($data['speed']) ? $data['speed'] : null,
            isset($data['gs']) ? $data['gs'] : null,
            isset($data['tas']) ? $data['tas'] : null,
            isset($data['ias']) ? $data['ias'] : null,
        ];

        $values = array_filter($values, 'strlen');

        if (count($values)) {
            $speed = array_shift($values);
        } else {
            $speed = null;
        }

        $this->speed = $speed;
    }

    private function setSeenAt($data)
    {
        $this->seen_at = isset($data['seen']) ? $data['seen'] : null;
    }

    private function setMessages($data)
    {
        $this->messages = isset($data['messages']) ? $data['messages'] : null;
    }

    private function setRssi($data)
    {
        $this->rssi = isset($data['rssi']) ? $data['rssi'] : null;
    }

    private function setEmergency($data)
    {
        $this->emergency = isset($data['emergency']) ? $data['emergency'] : null;
    }

    public $icao;
    public $category;
    public $squawk;
    public $flight;
    public $lat;
    public $lon;
    public $altitude;
    public $vert_rate;
    public $track;
    public $speed;
    public $seen_at;
    public $messages;
    public $rssi;
    public $emergency;
    public $registration;
    public $aircraft_type;
    public $wtc;
    public $aircraft_desc;
    public $nav_altitude_mcp;
    public $nav_qnh;
    public $nav_heading;
    public $mach;
    public $mag_heading;
    public $roll;
    public $ias;
    public $tas;
    public $geom_rate;
    public $nic;
    public $rc;

    private function setRegistration($data)
    {
        $this->registration = isset($data['registration']) && trim((string)$data['registration']) !== ''
            ? trim((string)$data['registration'])
            : null;
    }

    private function setAircraftType($data)
    {
        $this->aircraft_type = isset($data['aircraft_type']) && trim((string)$data['aircraft_type']) !== ''
            ? trim((string)$data['aircraft_type'])
            : null;
    }

    private function setWtc($data)
    {
        $this->wtc = isset($data['wtc']) && trim((string)$data['wtc']) !== ''
            ? trim((string)$data['wtc'])
            : null;
    }

    private function setAircraftDesc($data)
    {
        $val = $data['aircraft_desc'] ?? $data['desc'] ?? null;
        $this->aircraft_desc = $val !== null && trim((string)$val) !== ''
            ? trim((string)$val)
            : null;
    }

    private function setNavAltitudeMcp($data)
    {
        $this->nav_altitude_mcp = (isset($data['nav_altitude_mcp']) && is_numeric($data['nav_altitude_mcp']))
            ? (float) $data['nav_altitude_mcp']
            : null;
    }

    private function setNavQnh($data)
    {
        $this->nav_qnh = (isset($data['nav_qnh']) && is_numeric($data['nav_qnh']))
            ? (float) $data['nav_qnh']
            : null;
    }

    private function setNavHeading($data)
    {
        $this->nav_heading = (isset($data['nav_heading']) && is_numeric($data['nav_heading']))
            ? (float) $data['nav_heading']
            : null;
    }

    private function setMach($data)
    {
        $this->mach = (isset($data['mach']) && is_numeric($data['mach']))
            ? (float) $data['mach']
            : null;
    }

    private function setMagHeading($data)
    {
        $this->mag_heading = (isset($data['mag_heading']) && is_numeric($data['mag_heading']))
            ? (float) $data['mag_heading']
            : null;
    }

    private function setRoll($data)
    {
        $this->roll = (isset($data['roll']) && is_numeric($data['roll']))
            ? (float) $data['roll']
            : null;
    }

    private function setIas($data)
    {
        $this->ias = (isset($data['ias']) && is_numeric($data['ias']))
            ? (float) $data['ias']
            : null;
    }

    private function setTas($data)
    {
        $this->tas = (isset($data['tas']) && is_numeric($data['tas']))
            ? (float) $data['tas']
            : null;
    }

    private function setGeomRate($data)
    {
        $this->geom_rate = (isset($data['geom_rate']) && is_numeric($data['geom_rate']))
            ? (float) $data['geom_rate']
            : null;
    }

    private function setNic($data)
    {
        $this->nic = (isset($data['nic']) && is_numeric($data['nic']))
            ? (int) $data['nic']
            : null;
    }

    private function setRc($data)
    {
        $this->rc = (isset($data['rc']) && is_numeric($data['rc']))
            ? (float) $data['rc']
            : null;
    }
}
