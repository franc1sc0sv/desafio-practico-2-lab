<?php

namespace App\Controllers;

use App\Services\DatabaseService;
use React\Http\Message\Response;
use App\Utils\Logger;

class CategoryController
{
    public function index(): Response
    {
        try {
            $db = new DatabaseService();
            $pdo = $db->getConnection();

            $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
            $categories = $stmt->fetchAll();

            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($categories)

            );
        } catch (\Throwable $e) {
            Logger::error("Error al obtener categorías: " . $e->getMessage());
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Error interno del servidor'])
            );
        }
    }
}
