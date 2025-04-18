<?php

namespace App\Services;

use App\Utils\Logger;
use PDO;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Respect\Validation\Validator as v;

class AuthService
{
    public function __construct(private PDO $pdo, private string $jwtSecret) {}

    public function register(array $data): array|string
    {
        try {
            $validation = $this->validateRegister($data);
            if ($validation !== true) return $validation;

            $hash = password_hash($data['password'], PASSWORD_BCRYPT);

            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?) RETURNING id, email, nombre");
            $stmt->execute([$data['nombre'], $data['email'], $hash]);

            return $stmt->fetch();
        } catch (\Throwable $e) {
            Logger::error("Error al registrar usuario: " . $e->getMessage());
            return "Error al registrar usuario: " . $e->getMessage();
        }
    }

    public function login(array $data): string|false
    {
        try {
            $validation = $this->validateLogin($data);
            if ($validation !== true) return $validation;

            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($data['password'], $user['password_hash'])) {
                return false;
            }

            $payload = [
                'sub' => $user['id'],
                'email' => $user['email'],
                'iat' => time(),
                'exp' => time() + (int)$_ENV['JWT_EXPIRATION'],
            ];

            return JWT::encode($payload, $this->jwtSecret, 'HS256');
        } catch (\Throwable $e) {
            Logger::error("Error al iniciar sesión: " . $e->getMessage());
            return "Error al iniciar sesión: " . $e->getMessage();
        }
    }

    public function getProfileByUserId(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (\Throwable $e) {
            Logger::error("Error al obtener perfil por token: " . $e->getMessage());
            return [];
        }
    }

    private function validateRegister(array $data): true|string
{
    // Validación de nombre
    if (empty($data['nombre']) || preg_match('/^[0-9\s]*$/', $data['nombre'])) {
        return "El nombre no debe contener solo números.";
    }

    // Validación de email
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return "El email no es válido.";
    }

    // Validación de contraseña
    if (empty($data['password']) || strlen($data['password']) < 6) {
        return "La contraseña debe tener al menos 6 caracteres.";
    }

    // Validación de si el email ya está en uso
    $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
    $stmt->execute([$data['email']]);
    $count = $stmt->fetchColumn();

    if ($count !== 0) {
        return "El email ya está en uso.";
    }

    return true;
}

    

    private function validateLogin(array $data): true|string
    {
        try {
            $validator = v::key('email', v::email())
                ->key('password', v::stringType()->notEmpty());

            return $validator->validate($data) ? true : 'Email o contraseña inválidos.';
        } catch (\Throwable $e) {
            Logger::error("Error al validar login: " . $e->getMessage());
            return "Error al validar login: " . $e->getMessage();
        }
    }

    public function getSecret(): string
    {
        return $this->jwtSecret;
    }
}
