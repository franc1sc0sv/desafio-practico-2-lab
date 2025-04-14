<?php

namespace App\Controllers;

use App\Middlewares\RequestParser;
use App\Utils\Logger;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use App\Services\AuthService;

class AuthController
{
    public function __construct(private AuthService $authService) {}


    public function showRegister(): Response
    {
        try {
            $html = file_get_contents(__DIR__ . '/../../public/register.html');
            return new Response(200, ['Content-Type' => 'text/html'], $html);
        } catch (\Throwable $e) {
            Logger::error("Error al mostrar el formulario de registro: " . $e->getMessage());
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno del servidor']));
        }
    }

    public function showLogin(): Response
    {
        try {
            $html = file_get_contents(__DIR__ . '/../../public/login.html');
            return new Response(200, ['Content-Type' => 'text/html'], $html);
        } catch (\Throwable $e) {
            Logger::error("Error al mostrar el formulario de login: " . $e->getMessage());
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno del servidor']));
        }
    }


    public function register(ServerRequestInterface $request): Response
    {
        try {
            $data = RequestParser::parse($request);

            $result = $this->authService->register($data);

            if (is_string($result)) {
                return new Response(422, ['Content-Type' => 'application/json'], json_encode(['error' => $result]));
            }

            return new Response(201, ['Content-Type' => 'application/json'], json_encode(['success' => true, 'user' => $result]));
        } catch (\Throwable $e) {
            Logger::error("Error al registrar usuario: " . $e->getMessage());
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno del servidor']));
        }
    }

    public function login(ServerRequestInterface $request): Response
    {
        try {
            $data = RequestParser::parse(request: $request);

            $result = $this->authService->login($data);

            if ($result === false || is_string($result) && str_starts_with($result, 'Email')) {
                return new Response(401, ['Content-Type' => 'application/json'], json_encode(['error' => 'Credenciales inválidas']));
            }

            return new Response(200, [
                'Content-Type' => 'application/json',
                'Set-Cookie' => "token={$result}; HttpOnly; Path=/;",
            ], json_encode(['success' => true, 'token' => $result]));
        } catch (\Throwable $e) {
            Logger::error("Error al iniciar sesión: " . $e->getMessage());
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno del servidor']));
        }
    }
}
