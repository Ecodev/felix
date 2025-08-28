<?php

declare(strict_types=1);

namespace Ecodev\Felix;

final class FatalErrorHandler
{
    /**
     * Transform some specific PHP fatal errors into HTTP 500 with a JSON response
     * containing the error message (only). So that the client might show the error to the end-user.
     *
     * This must be called exactly **ONE TIME**.
     *
     * This will only work if HTTP headers were not sent already. So in development,
     * where `error_reporting` is most likely enabled, it might not work.
     */
    public static function register(): void
    {
        register_shutdown_function(function (): void {
            $message = error_get_last()['message'] ?? null;
            if (!headers_sent() && $message && str_starts_with($message, 'Maximum execution time of')) {
                header('content-type: application/json');
                http_response_code(500);

                echo json_encode(['message' => $message], JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        });
    }
}
