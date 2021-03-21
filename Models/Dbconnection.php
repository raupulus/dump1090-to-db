<?php
namespace App\Models;

use PDO;
use function implode;
use function var_dump;

/**
 * Clase que representa la conexión con la db y devuelve las colecciones de
 * consultas con instancias de sus modelos.
 */
class Dbconnection
{
    ## Almacena la conexión con la DB.
    private $dbh = null;

    ## Parámetros para conectar.
    private $params = [
        'DB_SGBD' => 'psql',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '5432',
        'DB_NAME' => 'dump1090',
        'DB_USER' => 'admin',
        'DB_COLLATE' => '',
        'DB_PASSWORD' => '',
        'OPTIONS' => [PDO::ATTR_PERSISTENT => true],
    ];

    /**
     * Dbconnection constructor.
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        ## Mezclo parámetros de conexión para la DB con los internos.
        $this->params = array_merge($this->params, $params);

        ## Creo la conexión con la DB y la almaceno.
        $try = 0;

        do {
            $try++;
            $this->dbh = $this->connect();
            usleep(300);  ## Pausa en milisegundos.
        } while (($this->dbh === null) && ($try <= 10));
    }

    /**
     * Inicia la conexión con la DB.
     *
     * @return \PDO|null Devuelve la conexión con la DB o null
     */
    private function connect()
    {
        try {
            $params = $this->params;
            $conn = $params['DB_SGBD'] . ":host=" . $params['DB_HOST'] . ";" .
                "dbname=" . $params['DB_NAME'] . ";" .
                "port=" . $params['DB_PORT'] . ";";

            return new PDO(
                $conn,
                $params['DB_USER'],
                $params['DB_PASSWORD'],
                $params['OPTIONS']
            );
        } catch (\Exception $e) {}

        return null;
    }

    /**
     * Ejecuta la query recibida
     *
     * @param string  $query  Cadena con la consulta
     *
     * @return null
     */
    public function execute($query)
    {
        if ($query) {
            try {
                $q = $this->dbh->prepare($query);
                $q->execute();

                if ($q) {
                    return $q;
                }
            } catch (\Exception $e) {
                echo "\n Error en la consulta, función execute() \n";
                var_dump($e);
            }
        }

        return null;
    }

    /**
     * Realiza la consulta y devuelve el número de resultados totales.
     */
    private function executeCount($subquery)
    {
        if ($subquery) {
            try {
                $query = <<<EOL
                    SELECT count(*) as total
                    FROM ($subquery) table_virtual
EOL;

                $q = $this->dbh->prepare($query);
                $q->execute();

                if ($q) {
                    return (int)$q->fetchColumn();
                }
            } catch (\Exception $e) {}
        }

        return 0;
    }

    /**
     * Devuelve todos los parámetros de conexión a la DB.
     * @return array Parámetros de configuración para la DB.
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * Cierra la conexión con la db.
     * @return Boolean Devuelve boolean indicando si fue cerrada o no.
     */
    public function close()
    {
        try {
            //$this->dbh->closeCursor();
            $this->dbh = null;
            return true;
        } catch (\Exception $e) {}

        return null;
    }

    /**
     * Insert all elements
     *
     * @param Object $rows
     *
     * @return array
     */
    public function insert(string $table, Object $rows)
    {

        $inserts = 0;

        foreach ($rows as $row) {
            $query = <<<EOL
            INSERT INTO $table
            VALUES $row
                
EOL;
            if ($this->execute($query)) {
                $inserts++;
            }
        }

        return [
            'inserts' => $inserts,
        ];
    }

    /**
     * Almacena los elementos recibidos.
     *
     * @param $airflights
     */
    public function saveAirflight($airflights)
    {
        foreach ($airflights as $airflight) {

            $lon = $airflight->lon ?? 'null';
            $lat = $airflight->lat ?? 'null';
            $altitude = $airflight->altitude ?? 'null';
            $vert_rate = $airflight->vert_rate ?? 'null';
            $track = $airflight->track ?? 'null';
            $rssi = $airflight->rssi ?? 'null';
            $speed = $airflight->speed ?? 'null';
            $messages = $airflight->messages ?? 'null';

            $query = <<<EOL
            INSERT INTO reports (icao, category, squawk, flight, lon, lat,
                                 altitude, vert_rate, track, speed, messages,
                                 seen_at, rssi, emergency)
            VALUES 
            (
                '$airflight->icao', 
                '$airflight->category',
                '$airflight->squawk',
                '$airflight->flight',
                $lon,
                $lat,
                $altitude,
                $vert_rate,
                $track,
                $speed,
                $messages,
                '$airflight->seen_at',
                $rssi,
                '$airflight->emergency'
            );
EOL;

            if ($query) {
                echo "Insertando:\n $query";

                $this->execute($query);
            }
        }
    }

    /**
     * Obtiene los últimos vuelos limitados a la cantidad recibida.
     *
     * @param int $limit Cantidad de vuelos a recibir.
     *
     * @return null
     */
    public function getLastsAirflight($limit = 10)
    {
        $query = <<<EOL
            SELECT * FROM reports
            ORDER BY seen_at DESC
            LIMIT $limit
            ;
EOL;

        if ($query) {
            echo "Consultando:\n $query";

            return $this->execute($query);
        }

        return null;
    }

    /**
     * Elimina los registros de vuelos recibidos.
     *
     * @param array $ids Array con el id de los elementos a borrar.
     *
     * @return null
     */
    public function deleteAirflight(array $ids)
    {
        $idsString = implode(',', $ids);

        $query = <<<EOL
            DELETE FROM reports
            WHERE id IN $idsString
            ;
EOL;

        if ($query) {
            echo "Eliminando: $query";

            return $this->execute($query);
        }

        return null;
    }

    /**
     * Delete element by ID
     *
     * @param $id
     */
    public function delete($id)
    {

    }
}

?>
