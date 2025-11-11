<?php

namespace AccountingSystem\Config;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    public static function initialize(): void
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? 'mysql',
            'database'  => $_ENV['DB_NAME'] ?? 'accounting_system',
            'username'  => $_ENV['DB_USER'] ?? 'accounting_user',
            'password'  => $_ENV['DB_PASSWORD'] ?? 'accounting_pass_123',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => 'InnoDB',
            'options'   => [
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Set the fetch mode for all queries
        Capsule::connection()->setFetchMode(PDO::FETCH_ASSOC);
    }
}