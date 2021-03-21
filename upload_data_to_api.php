<?php

namespace App;
use App\Helpers\Log;
use App\Models\Dbconnection;
use Symfony\Component\Dotenv\Dotenv;
use function define;

require 'vendor/autoload.php';

## Overwrite existing env variables
$dotenv = new Dotenv();
$dotenv->overload(__DIR__.'/.env');

## Environment vars

define('DEBUG', isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] : true);

## DB

define('DB_CONNECTION', isset($_ENV['DB_CONNECTION']) ? $_ENV['DB_CONNECTION'] : 'psql');
define('DB_HOST', isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : '127.0.0.1');
define('DB_PORT', isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '5432');
define('DB_DATABASE', isset($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : 'dump1090');
define('DB_USERNAME', isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'dbuser');
define('DB_PASSWORD', isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : '');


?>
