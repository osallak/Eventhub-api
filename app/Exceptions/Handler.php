<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        // catch UnauthorizedException
        $this->renderable(
            function (AccessDeniedHttpException $e, $request) {
                return response()->json(['success' => false, 'errors' => [__('auth.unauthorized')]]);
            }
        );
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json(['success' => false, 'errors' => [__('auth.unauthenticated')]], 403);
    }

    protected function invalidJson($request, ValidationException $exception)
    {
        $errors = [];
        foreach ($exception->errors() as $key => $value) {
            $errors = array_merge($errors, $value);
        }

        return response()->json(['success' => false, 'errors' => $errors]);
    }
}
