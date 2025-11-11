<?php

use AccountingSystem\Config\Database;
use AccountingSystem\Config\Dependencies;
use AccountingSystem\Middleware\CorsMiddleware;
use AccountingSystem\Middleware\AuthenticationMiddleware;
use AccountingSystem\Middleware\ErrorHandlingMiddleware;
use AccountingSystem\Api\Routes;
use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Middleware\ErrorMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create Slim app
$app = AppFactory::create();

// Add routing middleware
$app->addRoutingMiddleware();

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Add CORS middleware
$app->add(new CorsMiddleware());

// Add error handling middleware
$app->addErrorMiddleware(true, true, true);

// Set up dependencies
$container = $app->getContainer();
Dependencies::initialize($container);

// Set up database
Database::initialize();

// Add authentication middleware (except for login and register endpoints)
$app->add(new AuthenticationMiddleware([
    '/api/auth/login',
    '/api/auth/register',
    '/api/docs',
    '/api/docs.json'
]));

// Register routes
Routes::register($app);

// Add JSON pretty print for development
if ($_ENV['APP_DEBUG'] === 'true') {
    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    });
}

$app->run();