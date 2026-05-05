<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust ngrok/reverse proxy headers so generated URLs stay HTTPS.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserRole::class,
            'permission' => \App\Http\Middleware\EnsureUserPermission::class,
            'branch.access' => \App\Http\Middleware\EnsureBranchRouteAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            $isForbidden = $e instanceof AuthorizationException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403);

            if (! $isForbidden) {
                return null;
            }

            if (! ($request->is('admin') || $request->is('admin/*'))) {
                return null;
            }

            $user = $request->user();
            if ($user && ! $user->hasAnyRole(['owner', 'admin'])) {
                return redirect()
                    ->route('dashboard')
                    ->with('error', 'Akses Backoffice Admin hanya untuk owner/admin.');
            }

            return null;
        });
    })->create();
