<?php

namespace App\Utils;

class Logger
{
    public static function error(string $message): void
    {
        $date = date('Y-m-d H:i:s');
        error_log("[$date] $message");
    }
}
