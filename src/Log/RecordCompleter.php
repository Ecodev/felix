<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log;

use Ecodev\Felix\Model\CurrentUser;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class RecordCompleter implements ProcessorInterface
{
    public function __construct(
        private readonly string $baseUrl,
    ) {}

    /**
     * Complete a log record with extra data, including any global stuff relevant to the app.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = array_merge($record->extra, $this->getEnvData());

        return $record->with(
            context: $this->redactSensitiveData($record->context),
            extra: $this->redactSensitiveData($extra),
        );
    }

    /**
     * Retrieve dynamic information from environment to be logged.
     */
    private function getEnvData(): array
    {
        $user = CurrentUser::get();

        if (PHP_SAPI === 'cli') {
            global $argv;
            $ip = !empty(getenv('REMOTE_ADDR')) ? getenv('REMOTE_ADDR') : 'script';
            $url = implode(' ', $argv);
            $referer = '';
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $url = $this->baseUrl . $_SERVER['REQUEST_URI'];
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
        }

        $request = $_REQUEST;
        $request = $this->redactSensitiveData($request);

        $envData = [
            'creator_id' => $user?->getId(),
            'login' => $user?->getLogin() ?: '<anonymous>',
            'url' => $url,
            'referer' => $referer,
            'request' => json_encode($request, JSON_PRETTY_PRINT),
            'ip' => $ip,
        ];

        return $envData;
    }

    /**
     * Redact sensitive values from the entire data structure.
     */
    private function redactSensitiveData(array $request): array
    {
        foreach ($request as $key => &$value) {
            if (in_array($key, [
                'password',
                'passwordConfirmation',
            ], true)) {
                $value = '***REDACTED***';
            } elseif (is_array($value)) {
                $value = $this->redactSensitiveData($value);
            }
        }

        return $request;
    }
}
