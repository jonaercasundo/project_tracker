<?php

use App\Http\Middleware\CheckCompany;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/jarvis.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'company.context' => CheckCompany::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $exception): bool {
            return $request->is('api/jarvis/*') || $request->expectsJson();
        });
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if (! $request->is('api/jarvis/*') || $response->getStatusCode() < 400) {
                return $response;
            }

            $status = $response->getStatusCode();
            $body = json_decode($response->getContent(), true) ?? [];
            $message = $status >= 500 ? 'Server error.' : ($body['message'] ?? Response::$statusTexts[$status] ?? 'Request failed.');
            $payload = ['success' => false, 'data' => null, 'meta' => (object) [], 'message' => $message];
            if (isset($body['errors'])) {
                $payload['errors'] = $body['errors'];
            }
            $response->setContent(json_encode($payload));
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        });
    })->create();
