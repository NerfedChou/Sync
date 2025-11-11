<?php

use DI\Container;
use Slim\Factory\AppFactory;
use AccountingSystem\Config\Database;
use AccountingSystem\Config\Dependencies;
use AccountingSystem\Api\Routes;
use AccountingSystem\Middleware\CorsMiddleware;

require __DIR__ . '/vendor/autoload.php';

// Initialize database
Database::initialize();

// Create DI container
$container = new Container();
Dependencies::initialize($container);

// Create Slim app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add middleware
$app->add(new CorsMiddleware());

// Add routing
Routes::register($app);

// Add error handling
$app->addErrorMiddleware(true, true, true);

return $app;