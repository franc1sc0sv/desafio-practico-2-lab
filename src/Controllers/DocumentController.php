<?php

namespace App\Controllers;

use App\Middlewares\RequestParser;
use App\Services\DocumentService;
use App\Utils\Logger;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

class DocumentController
{


    public function __construct(private DocumentService $documentService) {}


    public function show(): Response
    {
        try {
            $html = file_get_contents(__DIR__ . '/../../public/documents.html');
            return new Response(200, ['Content-Type' => 'text/html'], $html);
        } catch (\Throwable $e) {
            Logger::error("Error al mostrar la página de documentos: " . $e->getMessage());
            return $this->error($e);
        }
    }


    public function index(ServerRequestInterface $request): Response
    {
        try {
            $userId = $request->getAttribute('user')->sub;
            $docs = $this->documentService->listDocuments($userId);

            return new Response(200, ['Content-Type' => 'application/json'], json_encode($docs));
        } catch (\Throwable $e) {
            Logger::error("Error al listar documentos: " . $e->getMessage());
            return $this->error($e);
        }
    }


    public function create(ServerRequestInterface $request): Response
    {
        try {

            $userId = $request->getAttribute('user')->sub;
            $files = $request->getUploadedFiles();

            if (!isset($files['archivo'])) {
                throw new \RuntimeException("No se encontró el archivo en la solicitud");
            }

            $this->documentService->uploadDocument($userId, $files['archivo']);

            return new Response(201, ['Content-Type' => 'application/json'], json_encode(['success' => 'Archivo subido']));
        } catch (\Throwable $e) {
            Logger::error("Error al crear documento: " . $e->getMessage());
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno del servidor']));
        }
    }


    public function delete(ServerRequestInterface $request, int $id): Response
    {
        try {
            $userId = $request->getAttribute('user')->sub;

            $this->documentService->deleteDocument($userId, $id);

            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['success' => 'Documento eliminado']));
        } catch (\Throwable $e) {
            Logger::error("Error al eliminar documento: " . $e->getMessage());
            return $this->error($e);
        }
    }

    private function error(\Throwable $e): Response
    {
        return new Response(500, ['Content-Type' => 'application/json'], json_encode([
            'error' => $e->getMessage(),
        ]));
    }
}
