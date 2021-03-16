<?php
namespace App\Helpers;

/**
 * Class Log
 *
 * @package App\Helpers
 */
class Log
{
    public static function error($msg = '')
    {
        echo "\n$msg\n";
    }

    public static function success($msg = '')
    {
        echo "\n$msg\n";
    }

    public static function info($msg = '')
    {
        echo "\n$msg\n";
    }
}
