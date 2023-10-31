<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // if request is api then return json else return parent::render($request, $exception)
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'status_code' => 401,
                        'message' => 'Unauthenticated',
                    ], 401);
                }
                
                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'status_code' => 404,
                        'message' => 'Not Found',
                    ], 404);
                }

                $isContainLogin = strpos($e->getMessage(), 'login');

                if ($isContainLogin !== false) {
                    return response()->json([
                        'success' => false,
                        'status_code' => 401,
                        'message' => 'Invalid credentials',
                    ], 401);
                }

                return response()->json([
                    'success' => false,
                    'status_code' => 500,
                    'message' => $e->getMessage(),
                ], 500);
            }
        });
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $request->expectsJson()
            ? response()->json(['message' => 'Unauthenticated.'], 401)
            : redirect()->guest(route('login'));
    }
}
