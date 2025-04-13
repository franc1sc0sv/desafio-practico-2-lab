<?php

namespace App\Controllers;

use App\Services\DatabaseService;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use App\Utils\Logger;
use App\Middlewares\RequestParser;

class ContactController
{
    public function show(): Response
    {
        $html = file_get_contents(__DIR__ . '/../../public/contact.html');

        return new Response(
            200,
            ['Content-Type' => 'text/html'],
            $html
        );
    }

    public function submit(ServerRequestInterface $request): Response
    {
        try {
            $db = new DatabaseService();
            $pdo = $db->getConnection();

            $body = RequestParser::parse($request);

            $name = $body['name'] ?? '';
            $email = $body['email'] ?? '';
            $message = $body['message'] ?? '';

            if (!$name || !$email || !$message) {
                return new Response(
                    400,
                    ['Content-Type' => 'application/json'],
                    json_encode(['error' => 'Todos los campos son obligatorios'])
                );
            }

            $sql = "INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':message' => $message
            ]);

            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['success' => 'Mensaje enviado correctamente'])
            );
        } catch (\Throwable $e) {
            Logger::error("Error en ContactController: " . $e->getMessage());
            return new Response(
                500,
                ['Content-Type' => 'text/plain'],
                'Error interno del servidor'
            );
        }
    }
}
