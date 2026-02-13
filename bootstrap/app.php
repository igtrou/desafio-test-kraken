<?php

use App\Domain\Exceptions\DomainValidationException;
use App\Domain\Exceptions\ForbiddenOperationException;
use App\Domain\MarketData\Exceptions\InvalidSymbolException;
use App\Domain\MarketData\Exceptions\ProviderRateLimitException;
use App\Domain\MarketData\Exceptions\ProviderUnavailableException;
use App\Domain\MarketData\Exceptions\QuoteNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\AssignRequestId::class);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'quotation.auth' => \App\Http\Middleware\EnsureQuotationApiAuthentication::class,
            'quotation.admin' => \App\Http\Middleware\EnsureQuotationAdminAuthorization::class,
            'gateway.only' => \App\Http\Middleware\EnsureRequestFromGateway::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApiRequest = static fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        $buildApiErrorResponse = static function (
            Request $request,
            string $message,
            string $errorCode,
            int $status,
            array $debugDetails = []
        ): JsonResponse {
            $requestId = $request->attributes->get('request_id');

            if (! is_string($requestId) || $requestId === '') {
                $requestId = (string) Str::uuid();
            }

            $payload = [
                'message' => $message,
                'error_code' => $errorCode,
                'request_id' => $requestId,
            ];

            if (config('app.debug') && $debugDetails !== []) {
                $payload['details'] = $debugDetails;
            }

            return response()->json($payload, $status);
        };

        $renderApiException = static function (
            string $exceptionClass,
            string $message,
            string $errorCode,
            int $status,
            ?callable $detailsResolver = null
        ) use ($exceptions, $isApiRequest, $buildApiErrorResponse): void {
            $exceptions->render(function (Throwable $exception, Request $request) use (
                $exceptionClass,
                $isApiRequest,
                $buildApiErrorResponse,
                $message,
                $errorCode,
                $status,
                $detailsResolver
            ) {
                if (! $exception instanceof $exceptionClass || ! $isApiRequest($request)) {
                    return null;
                }

                $errorDetails = $detailsResolver !== null
                    ? $detailsResolver($exception)
                    : ['exception' => $exception->getMessage()];

                return $buildApiErrorResponse($request, $message, $errorCode, $status, $errorDetails);
            });
        };

        $renderApiException(
            InvalidSymbolException::class,
            'Invalid symbol format.',
            'invalid_symbol',
            422
        );

        $renderApiException(
            QuoteNotFoundException::class,
            'Quote not found for the requested symbol.',
            'quote_not_found',
            404
        );

        $renderApiException(
            ProviderUnavailableException::class,
            'Market data provider is unavailable.',
            'provider_unavailable',
            503,
            static fn (ProviderUnavailableException $exception): array => [
                'provider' => $exception->provider,
                'exception' => $exception->getMessage(),
            ]
        );

        $renderApiException(
            ProviderRateLimitException::class,
            'Market data provider rate limit reached.',
            'provider_rate_limited',
            429,
            static fn (ProviderRateLimitException $exception): array => [
                'provider' => $exception->provider,
                'exception' => $exception->getMessage(),
            ]
        );

        $renderApiException(
            DomainValidationException::class,
            'Validation failed.',
            'validation_error',
            422,
            static fn (DomainValidationException $exception): array => [
                'errors' => $exception->errors,
            ]
        );

        $renderApiException(
            ForbiddenOperationException::class,
            'You are not allowed to perform this action.',
            'forbidden',
            403
        );

        $exceptions->render(function (DomainValidationException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ], 422);
        });

        $exceptions->render(function (ForbiddenOperationException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return null;
            }

            return response($exception->getMessage(), 403);
        });

        $renderApiException(
            ValidationException::class,
            'Validation failed.',
            'validation_error',
            422,
            static fn (ValidationException $exception): array => [
                'errors' => $exception->errors(),
            ]
        );

        $renderApiException(
            AuthenticationException::class,
            'Authentication required.',
            'unauthenticated',
            401
        );

        $renderApiException(
            AuthorizationException::class,
            'You are not allowed to perform this action.',
            'forbidden',
            403
        );

        $renderApiException(
            ThrottleRequestsException::class,
            'Too many requests.',
            'too_many_requests',
            429
        );

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($isApiRequest, $buildApiErrorResponse) {
            if (! $isApiRequest($request)) {
                return null;
            }

            $status = $exception->getStatusCode();
            $errorCode = match ($status) {
                403 => 'forbidden',
                404 => 'not_found',
                405 => 'method_not_allowed',
                419 => 'csrf_mismatch',
                default => 'http_error',
            };

            $message = match ($status) {
                403 => 'You are not allowed to perform this action.',
                404 => 'Resource not found.',
                405 => 'HTTP method not allowed for this endpoint.',
                419 => 'CSRF token mismatch. Reload the page and try again.',
                default => 'HTTP request error.',
            };

            return $buildApiErrorResponse($request, $message, $errorCode, $status, [
                'exception' => $exception->getMessage(),
            ]);
        });

        $renderApiException(
            Throwable::class,
            'Unexpected server error.',
            'internal_error',
            500,
            static fn (Throwable $exception): array => [
                'exception' => $exception->getMessage(),
                'class' => $exception::class,
            ]
        );
    })->create();
