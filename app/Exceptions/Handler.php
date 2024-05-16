<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Client\RequestException;

use Illuminate\Support\Facades\Log;

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
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        Log::debug("%%%%%%%%%%%%%%% EXCEPTION %%%%%%%%%%%%%%%%%%");

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            Log::debug($statusCode);
            // Return a view if it's not a JSON request
            // if (!$request->expectsJson()) {
            //     return response()->view("errors.{$statusCode}", [], $statusCode);
            // }
            // Fallback to JSON if the client expects JSON
            return response()->json([
                'error' => 'HTTP Error',
                'status' => $statusCode,
                'message' => Response::$statusTexts[$statusCode] ?? 'Unknown status'
            ], $statusCode);
        } elseif ($exception instanceof ValidationException) {
            return $this->invalidJson($request, $exception);
        } elseif ($exception instanceof RequestException) {

            return $this->invalidReq($request, $exception);
        }

        return parent::render($request, $exception);
    }

    protected function invalidJson($request, ValidationException $exception)
    {
        // Adjusted to match the HTTP error format
        return response()->json([
            'error' => 'Validation Error',
            'status' => 422,
            'message' => $exception->errors()
        ], 422);
    }
    protected function invalidReq($request, RequestException $exception)
    {
        $response = $exception->getMessage();

        // Extract JSON from the response string
        $jsonString = trim(substr($response, strpos($response, '{')));

        // Decode the JSON string
        $json = json_decode($jsonString);

        // Extract the message
        if ($json && isset($json->message)) {
            $message = $json->message;
        } else {
            $message = "No message found in the JSON response.";
        }
        // Adjusted to match the HTTP error format
        return response()->json([
            'error' => 'Request Error',
            'status' => $exception->getCode(),
            'message' => $message
        ], $exception->getCode());
    }
}
