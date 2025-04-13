<?php

namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface;

class RequestParser
{
    public static function parse(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');
        $raw = (string) $request->getBody();

        if (str_contains($contentType, 'application/json')) {
            return json_decode($raw, true) ?? [];
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($raw, $parsed);
            return $parsed ?? [];
        }

        return [];
    }
}
