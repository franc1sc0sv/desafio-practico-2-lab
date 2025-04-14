<?php

namespace App;

use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use App\Controllers\HomeController;
use App\Controllers\DataController;
use App\Services\DatabaseService;
use App\Controllers\AuthController;
use App\Controllers\DocumentController;
use App\Controllers\EventController;
use App\Middlewares\JwtMiddleware as MiddlewaresJwtMiddleware;
use App\Services\AuthService;
use App\Services\DocumentService;
use App\Utils\Logger;
use Middlewares\JwtMiddleware;

use function App\Middlewares\withParsedBody;

class Router
{
    public static function handle(ServerRequestInterface $request): Response
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // DB
        $db = new DatabaseService();
        $pdo = $db->getConnection();

        // JWT
        $jwtSecret = $_ENV['JWT_SECRET'];

        // Auth
        $authService = new AuthService($pdo, $jwtSecret);
        $authController = new AuthController($authService);




        return match (true) {

            // Auth routes
            // Public routes
            $path === '/register' && $method === 'GET' => $authController->showRegister(),
            $path === '/login' && $method === 'GET' => $authController->showLogin(),

            // API routes
            $path === '/api/register' && $method === 'POST' => $authController->register($request),
            $path === '/api/login' && $method === 'POST' => $authController->login($request),

            // Home routes
            $path === '/' && $method === 'GET' => (new HomeController())->show(),


            // Events routes
            $path === '/api/events' && $method === 'GET' => (new EventController())->stream(),

            // Public routes
            $path === '/documentos' && $method === 'GET' => (new DocumentController(new DocumentService($pdo)))->show(),


            // Documentos API 
            $path === '/api/documentos' && $method === 'GET' => (new MiddlewaresJwtMiddleware($authService))->handle($request, fn($req) => (new DocumentController(new DocumentService($pdo)))->index($req)),
            $path === '/api/documentos' && $method === 'POST' => (new MiddlewaresJwtMiddleware($authService))->handle($request, fn($req) => (new DocumentController(new DocumentService($pdo)))->create($req)),
            preg_match('#^/api/documentos/(\d+)$#', $path, $m) && $method === 'PUT' => (new MiddlewaresJwtMiddleware($authService))->handle($request, fn($req) => (new DocumentController(new DocumentService($pdo)))->update($req, (int)$m[1])),
            preg_match('#^/api/documentos/(\d+)$#', $path, $m) && $method === 'DELETE' => (new MiddlewaresJwtMiddleware($authService))->handle($request, fn($req) => (new DocumentController(new DocumentService($pdo)))->delete($req, (int)$m[1])),


            default => new Response(
                404,
                ['Content-Type' => 'text/plain'],
                "404 - Ruta no encontrada"
            ),
        };
    }
}
