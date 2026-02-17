<?php

namespace App\Helpers;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        // Add exception types that should not be logged
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {
            return $this->handleJsonResponse($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle JSON responses for exceptions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleJsonResponse($request, Throwable $exception)
    {
        // Default response
        $status = 500;
        $message = 'An unexpected error occurred.';
        $error = [];

        //  ValidationException
        if ($exception instanceof ValidationException) {
            $status = 422;
            $message = 'Validation error';
            $error= $exception->errors();
        }

        //  HttpExceptionInterface
        elseif ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'HTTP error';
        }

        //  ModelNotFoundException
        elseif ($exception instanceof ModelNotFoundException) {
            $status = 404;
            $message = 'Resource not found';
        }

        //  NotFoundHttpException
        elseif ($exception instanceof NotFoundHttpException) {
            $status = 404;
            $message = 'Endpoint not found';
        }

        //  MethodNotAllowedHttpException
        elseif ($exception instanceof MethodNotAllowedHttpException) {
            $status = 405;
            $message = 'Method not allowed';
        }

        //  AuthenticationException
        elseif ($exception instanceof AuthenticationException) {
            $status = 401;
            $message = 'Unauthenticated';
        }

        //  detailed error message in debug mode
        if (config('app.debug')) {
            $message = $exception->getMessage();
            $error['trace'] = $exception->getTrace();
        }

        return response()->json([
            'status'  => $status,
            'message' => $message,
            'error'  => $error,
        ], $status);
    }
}