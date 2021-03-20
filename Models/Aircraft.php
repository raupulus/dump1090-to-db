<?php

namespace App\Models;

/**
 * Class Aircraft
 *
 * Representa un vuelo.
 *
 * @package App\Models
 */
class Aircraft
{
    private $attributes = [
        'hex' => 'icao',
        'category' => 'category',
        'squawk' => 'squawk',
        'flight' => 'flight',
        'lat' => 'lat',
        'lon' => 'lon',
        'flight' => 'altitude',
        'flight' => 'vert_rate',
        'flight' => 'track',
        'flight' => 'speed',
        'flight' => 'seen_at',
        'flight' => 'rssi',
        'flight' => 'emergency',
    ];

    private $data = null;

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
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setSquawk($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setFlight($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setLon($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setLat($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setAltitude($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setVertRate($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setTrack($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setSpeed($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setSeenAt($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setRssi($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }

    private function setEmergency($data)
    {
        $this->icao = isset($data['hex']) ? $data['hex'] : null;
    }
}
