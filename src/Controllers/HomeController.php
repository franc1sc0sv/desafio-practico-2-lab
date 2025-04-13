<?php

namespace App\Controllers;

use React\Http\Message\Response;

class HomeController
{
    public function show(): Response
    {
        $html = file_get_contents(__DIR__ . '/../../public/home.html');

        return new Response(
            200,
            ['Content-Type' => 'text/html'],
            $html
        );
    }
}
