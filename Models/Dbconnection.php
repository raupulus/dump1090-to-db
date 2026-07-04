<?php
namespace App\Models;

use PDO;
use function implode;
use function var_dump;
use function array_fill;
use function count;

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
            sleep(1);  ## Pausa de un segundo.
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
        } catch (\Exception $e) {
            \App\Helpers\Log::error($e->getMessage());
        }

        return null;
    }

    /**
     * Ejecuta la query recibida
     *
     * @param string  $query  Cadena con la consulta
     *
     * @return null
     */
    public function execute($query, $params = [])
    {
        if ($query) {
            try {
                $q = $this->dbh->prepare($query);
                $q->execute($params);

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
            } catch (\Exception $e) {
                \App\Helpers\Log::error($e->getMessage());
            }
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
        } catch (\Exception $e) {
            \App\Helpers\Log::error($e->getMessage());
        }

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
            $query = <<<EOL
            INSERT INTO reports (icao, category, squawk, flight, lon, lat,
                                 altitude, vert_rate, track, speed, messages,
                                 seen_at, rssi, emergency)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
EOL;

            $params = [
                $airflight->icao,
                $airflight->category,
                $airflight->squawk,
                $airflight->flight,
                $airflight->lon ?? null,
                $airflight->lat ?? null,
                $airflight->altitude ?? null,
                $airflight->vert_rate ?? null,
                $airflight->track ?? null,
                $airflight->speed ?? null,
                $airflight->messages ?? null,
                $airflight->seen_at,
                $airflight->rssi ?? null,
                $airflight->emergency
            ];

            if ($query) {
                $this->execute($query, $params);
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
            SELECT id, icao, category, squawk, flight, lat, lon, altitude, 
            vert_rate, track, speed, seen_at, messages, rssi, emergency 
            FROM reports
            ORDER BY seen_at DESC
            LIMIT $limit
            ;
EOL;

        if ($query) {
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
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $query = <<<EOL
            DELETE FROM reports
            WHERE id IN ($placeholders)
            ;
EOL;

        if ($query) {
            echo "Eliminando: $query";

            return $this->execute($query, $ids);
        }

        return null;
    }

}
?>
