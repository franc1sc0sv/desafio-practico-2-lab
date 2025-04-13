<?php

namespace App;

use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use App\Controllers\HomeController;
use App\Controllers\ContactController;
use App\Controllers\DataController;
use App\Controllers\CategoryController;
use App\Controllers\EventController;
use function App\Middlewares\withParsedBody;

class Router
{
    public static function handle(ServerRequestInterface $request): Response
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        if (preg_match('#^/data/(\d+)$#', $path, $matches)) {
            $id = (int) $matches[1];
            $controller = new DataController();

            return match ($method) {
                'PUT'    => $controller->update($request, $id),
                'DELETE' => $controller->delete($id),
                default  => new Response(405, ['Content-Type' => 'text/plain'], 'Método no permitido')
            };
        }

        return match (true) {
            $path === '/' && $method === 'GET' => (new HomeController())->show(),
            $path === '/contact' && $method === 'GET' => (new ContactController())->show(),
            $path === '/contact' && $method === 'POST' => (new ContactController())->submit($request),

            $path === '/dashboard' && $method === 'GET' => (new DataController())->show(),
            $path === '/data' && $method === 'GET' => (new DataController())->index(),
            $path === '/data' && $method === 'POST' => (new DataController())->create($request),

            $path === '/categories' && $method === 'GET' => (new CategoryController())->index(),

            $path === '/events' && $method === 'GET' => (new EventController())->stream(),

            default => new Response(
                404,
                ['Content-Type' => 'text/plain'],
                "404 - Ruta no encontrada"
            ),
        };
    }
}
