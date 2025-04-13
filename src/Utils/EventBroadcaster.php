<?php

namespace App\Utils;

use React\Stream\ThroughStream;

class EventBroadcaster
{
    private static array $clients = [];

    public static function addClient(ThroughStream $stream): void
    {
        self::$clients[] = $stream;
    }

    public static function broadcast(array $data): void
    {
        $payload = "data: " . json_encode($data) . "\n\n";

        foreach (self::$clients as $key => $client) {
            if ($client->isWritable()) {
                $client->write($payload);
            } else {
                unset(self::$clients[$key]);
            }
        }
    }
}
