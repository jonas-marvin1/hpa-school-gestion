<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Declencheur de secours des rappels, tant qu'aucune tache cron
        // n'appelle `schedule:run`. Il s'execute apres la reponse et se
        // limite a un passage toutes les deux minutes.
        $middleware->appendToGroup('web', \App\Http\Middleware\DispatchDueReminders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

return $app;
