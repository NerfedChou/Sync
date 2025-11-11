<?php

namespace AccountingSystem\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use AccountingSystem\Services\AuthService;

class AuthenticationMiddleware
{
    private AuthService $authService;
    private array $publicRoutes;

    public function __construct(AuthService $authService, array $publicRoutes = [])
    {
        $this->authService = $authService;
        $this->publicRoutes = $publicRoutes;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $uri = $request->getUri()->getPath();
        
        // Skip authentication for public routes
        if ($this->isPublicRoute($uri)) {
            return $handler->handle($request);
        }

        // Get Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->createUnauthorizedResponse('Authorization token required');
        }

        $token = $matches[1];

        // Validate token
        $decoded = $this->authService->validateToken($token);
        
        if (!$decoded) {
            return $this->createUnauthorizedResponse('Invalid or expired token');
        }

        // Get user from token
        $user = $this->authService->getUserFromToken($token);
        
        if (!$user || !$user['is_active']) {
            return $this->createUnauthorizedResponse('User not found or inactive');
        }

        // Add user and token to request attributes
        $request = $request->withAttribute('user', $user);
        $request = $request->withAttribute('token', $token);
        $request = $request->withAttribute('company_id', $user['company_id']);
        $request = $request->withAttribute('user_id', $user['user_id']);
        $request = $request->withAttribute('user_role', $user['role']);

        return $handler->handle($request);
    }

    private function isPublicRoute(string $uri): bool
    {
        foreach ($this->publicRoutes as $route) {
            if (fnmatch($route, $uri)) {
                return true;
            }
        }
        return false;
    }

    private function createUnauthorizedResponse(string $message): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message
        ]));
        
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}