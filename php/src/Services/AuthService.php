<?php

namespace AccountingSystem\Services;

use AccountingSystem\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DateTime;

class AuthService
{
    private UserRepository $userRepository;
    private string $secretKey;
    private int $tokenExpiry;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'default_secret_key_change_in_production';
        $this->tokenExpiry = (int) ($_ENV['JWT_EXPIRY'] ?? 3600); // 1 hour default
    }

    public function login(string $username, string $password): array
    {
        try {
            // Find user by username or email
            $user = $this->userRepository->findByUsernameOrEmail($username);

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Check if user is active
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is inactive'];
            }

            // Update last login
            $this->userRepository->updateLastLogin($user['user_id']);

            // Generate JWT token
            $token = $this->generateToken($user);

            // Remove sensitive data
            unset($user['password_hash']);

            return [
                'success' => true,
                'user' => $user,
                'token' => $token,
                'expires_in' => $this->tokenExpiry
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    public function logout(string $token): array
    {
        try {
            // In a real implementation, you might want to blacklist the token
            // For now, we'll just return success
            return ['success' => true, 'message' => 'Logout successful'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Logout failed: ' . $e->getMessage()];
        }
    }

    public function refresh(string $token): array
    {
        try {
            // Decode current token
            $decoded = $this->decodeToken($token);

            if (!$decoded) {
                return ['success' => false, 'message' => 'Invalid token'];
            }

            // Get user from database
            $user = $this->userRepository->findById($decoded['sub']);

            if (!$user || !$user['is_active']) {
                return ['success' => false, 'message' => 'User not found or inactive'];
            }

            // Generate new token
            $newToken = $this->generateToken($user);

            return [
                'success' => true,
                'token' => $newToken,
                'expires_in' => $this->tokenExpiry
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Token refresh failed: ' . $e->getMessage()];
        }
    }

    public function validateToken(string $token): ?array
    {
        try {
            return $this->decodeToken($token);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserFromToken(string $token): ?array
    {
        try {
            $decoded = $this->decodeToken($token);
            
            if (!$decoded) {
                return null;
            }

            return $this->userRepository->findById($decoded['sub']);

        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateToken(array $user): string
    {
        $payload = [
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost',
            'aud' => $_ENV['APP_URL'] ?? 'http://localhost',
            'iat' => time(),
            'exp' => time() + $this->tokenExpiry,
            'sub' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'company_id' => $user['company_id']
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    private function decodeToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}