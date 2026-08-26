<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

/*
 * On shared cPanel hosting the application lives outside the web root and the
 * contents of public/ are moved into public_html. Pointing the public path at
 * that directory keeps public_path(), asset() and the "public" upload disk all
 * resolving to the same place, with no symlink involved.
 *
 * Set APP_PUBLIC_PATH=/home/<account>/public_html in .env on the server.
 * Left unset, everything behaves exactly as it does locally.
 */
if ($publicPath = env('APP_PUBLIC_PATH')) {
    $app->usePublicPath($publicPath);
}

return $app;
