<?php
namespace App\Models;

use PDO;

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
        'DB_CHARSET' => 'utf8mb4',
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
            usleep(80);  ## Pausa en milisegundos.
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
                "port=" . $params['DB_PORT'] . ";" .
                "charset=" . $params['DB_CHARSET'] . ";";

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
            } catch (\Exception $e) {}
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
     * @return Array Parámetros de configuración para la DB.
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
     * @param array $rows
     *
     * @return array
     */
    public function insert(Array $rows)
    {

        $inserts = 0;

        foreach ($rows as $row) {
            $query = <<<EOL
            INSERT
            VALUES
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
     * Delete element by ID
     *
     * @param $id
     */
    public function delete($id)
    {

    }
}

?>
