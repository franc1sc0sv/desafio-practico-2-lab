<?php

require __DIR__ . '/../vendor/autoload.php';

use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use Psr\Http\Message\ServerRequestInterface;
use App\Router;
use App\Services\DatabaseService;
use Dotenv\Dotenv;

$loop = Loop::get();

// Cargar .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Inicializar servicio de base de datos (PDO)
$db = new DatabaseService();

$server = new HttpServer(function (ServerRequestInterface $request) {
    return Router::handle($request);
});

$socket = new SocketServer("0.0.0.0:8000", [], $loop);
$server->listen($socket);

echo "Servidor escuchando en http://localhost:8000\n";

$loop->run();
