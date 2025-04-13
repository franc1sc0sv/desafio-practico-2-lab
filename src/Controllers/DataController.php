<?php

namespace App\Controllers;

use App\Middlewares\RequestParser;
use App\Services\DatabaseService;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use function App\Middlewares\withParsedBody;
use App\Utils\Logger;
use App\Utils\EventBroadcaster;

class DataController
{
    public function show(): Response
    {
        $html = file_get_contents(__DIR__ . '/../../public/data.html');

        return new Response(200, ['Content-Type' => 'text/html'], $html);
    }

    public function index(): Response
    {
        try {
            $db = new DatabaseService();
            $pdo = $db->getConnection();

            $sql = "SELECT e.id, e.title, e.description, e.created_at, e.updated_at, c.name as category
                    FROM entries e
                    JOIN categories c ON e.category_id = c.id
                    ORDER BY e.created_at DESC";

            $entries = $pdo->query($sql)->fetchAll();

            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($entries)
            );
        } catch (\Throwable $e) {
            Logger::error("Error al obtener datos: " . $e->getMessage());
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Error interno del servidor'])
            );
        }
    }

    public function create(ServerRequestInterface $request): Response
    {
        try {
            $db = new DatabaseService();
            $pdo = $db->getConnection();

            $body = RequestParser::parse($request);

            $title = $body['title'] ?? '';
            $description = $body['description'] ?? '';
            $category_id = $body['category_id'] ?? null;

            if (!$title || !$description || !$category_id) {
                return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Campos requeridos faltantes']));
            }

            $sql = "INSERT INTO entries (title, description, category_id) VALUES (:title, :description, :category_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':category_id' => $category_id
            ]);

            EventBroadcaster::broadcast(['event' => 'created']);

            return new Response(201, ['Content-Type' => 'application/json'], json_encode(['success' => 'Entrada creada']));
        } catch (\Throwable $e) {
            Logger::error("Error al crear entrada: " . $e->getMessage());
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno del servidor']));
        }
    }

    public function update(ServerRequestInterface $request, int $id): Response
    {
        try {
            $db = new DatabaseService();
            $pdo = $db->getConnection();

            $body = RequestParser::parse($request);

            $title = $body['title'] ?? '';
            $description = $body['description'] ?? '';
            $category_id = $body['category_id'] ?? null;

            if (!$title || !$description || !$category_id) {
                return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Campos requeridos faltantes']));
            }

            $sql = "UPDATE entries SET title = :title, description = :description, category_id = :category_id, updated_at = NOW() WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':category_id' => $category_id,
                ':id' => $id
            ]);

            EventBroadcaster::broadcast(['event' => 'updated']);

            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['success' => 'Entrada actualizada']));
        } catch (\Throwable $e) {
            Logger::error("Error al actualizar entrada: " . $e->getMessage());
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno del servidor']));
        }
    }

    public function delete(int $id): Response
    {
        try {
            $db = new DatabaseService();
            $pdo = $db->getConnection();

            $sql = "DELETE FROM entries WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            EventBroadcaster::broadcast(['event' => 'deleted']);

            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['success' => 'Entrada eliminada']));
        } catch (\Throwable $e) {
            Logger::error("Error al eliminar entrada: " . $e->getMessage());
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno del servidor']));
        }
    }
}
