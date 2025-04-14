<?php

namespace App\Utils;

class Logger
{
    public static function debug(string $message, $data = null): void
    {
        $date = date('Y-m-d H:i:s');
        echo "[$date] $message: \n";
        print_r($data);
        echo PHP_EOL;
    }

    public static function error(string $message, $error = null): void
    {
        $date = date('Y-m-d H:i:s');
        echo "[$date] ERROR: $message" . PHP_EOL;
        if ($error) {
            echo "[$date] ERROR: " . $error->getMessage() . PHP_EOL;
            echo "[$date] STACK TRACE: " . $error->getTraceAsString() . PHP_EOL;
            print_r($error);
        }
    }
}
