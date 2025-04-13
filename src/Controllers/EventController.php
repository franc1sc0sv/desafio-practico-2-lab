<?php

namespace App\Controllers;

use React\Http\Message\Response;
use React\Stream\ThroughStream;
use App\Utils\EventBroadcaster;

class EventController
{
    public function stream(): Response
    {
        $stream = new ThroughStream();

        $headers = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive'
        ];

        EventBroadcaster::addClient($stream);

        return new Response(200, $headers, $stream);
    }
}
