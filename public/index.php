<?php

require __DIR__ . '/../vendor/autoload.php';

use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use Psr\Http\Message\ServerRequestInterface;
use App\Router;
use App\Services\DatabaseService;
use Dotenv\Dotenv;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;

$loop = Loop::get();

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$db = new DatabaseService();

$http = new React\Http\HttpServer(
    new StreamingRequestMiddleware(),
    new LimitConcurrentRequestsMiddleware(100),
    new RequestBodyBufferMiddleware(sizeLimit: 25 * 1024 * 1024),
    new RequestBodyParserMiddleware(100 * 1024 * 1024, 1),
    function (ServerRequestInterface $request) {
        return Router::handle($request);
    }
);

$socket = new SocketServer("0.0.0.0:8000", [], $loop);
$http->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
