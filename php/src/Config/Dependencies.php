<?php

namespace AccountingSystem\Config;

use DI\Container;
use DI\ContainerBuilder;
use AccountingSystem\Services\AuthService;
use AccountingSystem\Services\AccountService;
use AccountingSystem\Services\TransactionService;
use AccountingSystem\Services\ReportService;
use AccountingSystem\Services\AnalyticsService;
use AccountingSystem\Repositories\UserRepository;
use AccountingSystem\Repositories\AccountRepository;
use AccountingSystem\Repositories\TransactionRepository;
use AccountingSystem\Repositories\CompanyRepository;
use AccountingSystem\Middleware\AuthenticationMiddleware;

class Dependencies
{
    public static function initialize(Container $container): void
    {
        // Authentication Service
        $container->set(AuthService::class, function (Container $container) {
            return new AuthService(
                $container->get(UserRepository::class)
            );
        });

        // Account Service
        $container->set(AccountService::class, function (Container $container) {
            return new AccountService(
                $container->get(AccountRepository::class)
            );
        });

        // Transaction Service
        $container->set(TransactionService::class, function (Container $container) {
            return new TransactionService(
                $container->get(TransactionRepository::class),
                $container->get(AccountRepository::class)
            );
        });

        // Report Service
        $container->set(ReportService::class, function (Container $container) {
            return new ReportService(
                $container->get(TransactionRepository::class),
                $container->get(AccountRepository::class)
            );
        });

        // Analytics Service
        $container->set(AnalyticsService::class, function (Container $container) {
            return new AnalyticsService(
                $container->get(TransactionRepository::class),
                $container->get(AccountRepository::class)
            );
        });

        // Repositories
        $container->set(UserRepository::class, function () {
            return new UserRepository();
        });

        $container->set(AccountRepository::class, function () {
            return new AccountRepository();
        });

        $container->set(TransactionRepository::class, function () {
            return new TransactionRepository();
        });

        $container->set(CompanyRepository::class, function () {
            return new CompanyRepository();
        });

        // Middleware
        $container->set(AuthenticationMiddleware::class, function (Container $container) {
            return new AuthenticationMiddleware(
                $container->get(AuthService::class)
            );
        });
    }
}