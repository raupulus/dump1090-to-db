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
        $this->setRssi($data);
        $this->setEmergency($data);
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
        $this->vert_rate = isset($data['vert_rate']) ? $data['vert_rate'] : null;
    }

    private function setTrack($data)
    {
        $this->track = isset($data['track']) ? $data['track'] : null;
    }

    private function setSpeed($data)
    {
        $this->speed = isset($data['speed']) ? $data['speed'] : null;
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
}
