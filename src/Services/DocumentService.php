<?php

namespace App\Services;

use App\Utils\EventBroadcaster;
use App\Utils\Logger;
use PDO;
use Respect\Validation\Validator as v;

class DocumentService
{

    private string $uploadBasePath = __DIR__ . '/../../storage/uploads';

    public function __construct(private PDO $pdo) {}

    public function listDocuments(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id, nombre, tipo, ruta, fecha_subida FROM archivos WHERE usuario_id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function uploadDocument(int $userId, $uploadedFile): void
    {
        try {
            Logger::debug('Uploaded file::', [$uploadedFile]);

            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $errorCode = $uploadedFile->getError();

                if ($errorCode !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño máximo permitido por el servidor.',
                        UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño permitido por el formulario.',
                        UPLOAD_ERR_PARTIAL    => 'El archivo no se subió completamente.',
                        UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal.',
                        UPLOAD_ERR_CANT_WRITE => 'No se pudo guardar el archivo.',
                        UPLOAD_ERR_EXTENSION  => 'Una extensión PHP detuvo la subida.',
                    ];

                    $message = $errorMessages[$errorCode] ?? "Error desconocido al subir el archivo.";
                    throw new \RuntimeException("❌ Error al subir archivo: $message");
                }
            }

            $nombre = $uploadedFile->getClientFilename();
            $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

            if (!in_array($extension, ['pdf', 'docx', 'txt'])) {
                throw new \RuntimeException("Tipo de archivo no permitido");
            }

            $userDir = "{$this->uploadBasePath}/{$userId}";
            if (!is_dir($userDir)) mkdir($userDir, 0777, true);

            $nombreUnico = uniqid() . "_" . basename($nombre);
            $ruta = "{$userDir}/{$nombreUnico}";

            $stream = $uploadedFile->getStream();
            file_put_contents($ruta, $stream->getContents());

            $stmt = $this->pdo->prepare("INSERT INTO archivos (usuario_id, nombre, ruta, tipo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $nombre, $ruta, $extension]);

            EventBroadcaster::broadcast(['event' => 'document_uploaded']);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e);
        }
    }


    // public function createDocument(int $userId, array $data): void
    // {
    //     try {
    //         $validator = v::key('nombre', v::stringType()->notEmpty())
    //             ->key('contenido', v::stringType()->notEmpty())
    //             ->key('tipo', v::in(['pdf', 'docx', 'txt']));

    //         if (!$validator->validate($data)) {
    //             throw new \RuntimeException("Datos inválidos");
    //         }

    //         $nombre = $data['nombre'];
    //         $tipo = strtolower($data['tipo']);
    //         $contenido = $data['contenido'];

    //         if (str_contains($contenido, ',')) {
    //             $contenido = explode(',', $contenido, 2)[1];
    //         }

    //         $userDir = "{$this->uploadBasePath}/{$userId}";
    //         if (!is_dir($userDir)) mkdir($userDir, 0777, true);

    //         $nombreUnico = uniqid() . "_" . basename($nombre);
    //         $ruta = "{$userDir}/{$nombreUnico}";

    //         file_put_contents($ruta, base64_decode($contenido));

    //         $stmt = $this->pdo->prepare("INSERT INTO archivos (usuario_id, nombre, ruta, tipo) VALUES (?, ?, ?, ?)");
    //         $stmt->execute([$userId, $nombre, $ruta, $tipo]);

    //         EventBroadcaster::broadcast(['event' => 'document_uploaded']);
    //     } catch (\Throwable $e) {
    //         Logger::error("Error al crear documento: " . $e->getMessage());
    //         throw new \RuntimeException("Error al crear documento: " . $e->getMessage());
    //     }
    // }

    public function updateDocument(int $userId, int $id, array $data): void
    {
        try {
            $validator = v::key('nombre', v::stringType()->notEmpty())
                ->key('tipo', v::in(['pdf', 'docx', 'txt']));

            if (!$validator->validate($data)) {
                throw new \RuntimeException("Datos inválidos");
            }

            $stmt = $this->pdo->prepare("SELECT * FROM archivos WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $userId]);

            if (!$stmt->fetch()) {
                throw new \RuntimeException("Archivo no encontrado");
            }

            $stmt = $this->pdo->prepare("UPDATE archivos SET nombre = ?, tipo = ? WHERE id = ?");
            $stmt->execute([$data['nombre'], $data['tipo'], $id]);

            EventBroadcaster::broadcast(['event' => 'document_updated']);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Error al actualizar documento: " . $e->getMessage());
        }
    }

    public function deleteDocument(int $userId, int $id): void
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM archivos WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $userId]);
            $archivo = $stmt->fetch();

            if (!$archivo) {
                throw new \RuntimeException("Archivo no encontrado");
            }

            if (file_exists($archivo['ruta'])) {
                unlink($archivo['ruta']);
            }

            $stmt = $this->pdo->prepare("DELETE FROM archivos WHERE id = ?");
            $stmt->execute([$id]);

            EventBroadcaster::broadcast(['event' => 'document_deleted']);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Error al eliminar documento: " . $e->getMessage());
        }
    }
}
