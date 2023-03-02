<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log;

use Ecodev\Felix\Model\CurrentUser;
use Laminas\Log\Processor\ProcessorInterface;

class EventCompleter implements ProcessorInterface
{
    public function __construct(private readonly string $baseUrl)
    {
    }

    /**
     * Complete a log event with extra data, including stacktrace and any global stuff relevant to the app.
     */
    public function process(array $event): array
    {
        $envData = $this->getEnvData();
        $event = array_merge($event, $envData);

        // If we are logging PHP errors, then we include all known information in message
        if ($event['extra']['errno'] ?? false) {
            $event['message'] .= "\nStacktrace:\n" . $this->getStacktrace();
        }

        // Security hide clear text password
        unset($event['extra']['password']);

        return $event;
    }

    /**
     * Retrieve dynamic information from environment to be logged.
     */
    private function getEnvData(): array
    {
        $user = CurrentUser::get();

        if (PHP_SAPI === 'cli') {
            global $argv;
            $request = $argv;
            $ip = !empty(getenv('REMOTE_ADDR')) ? getenv('REMOTE_ADDR') : 'script';
            $url = implode(' ', $argv);
            $referer = '';
        } else {
            $request = $_REQUEST;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $url = $this->baseUrl . $_SERVER['REQUEST_URI'];
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
        }

        $request = $this->removeSensitiveData($request);

        $envData = [
            'creator_id' => $user?->getId(),
            'login' => $user?->getLogin(),
            'url' => $url,
            'referer' => $referer,
            'request' => json_encode($request, JSON_PRETTY_PRINT),
            'ip' => $ip,
        ];

        return $envData;
    }

    /**
     * Remove password value from GraphQL variables well-known structure.
     */
    protected function removeSensitiveData(array $request): array
    {
        foreach ($request as &$r) {
            if (is_array($r)) {
                unset($r['variables']['password']);
            }
        }

        return $request;
    }

    /**
     * Returns the backtrace excluding the most recent calls to this function, so we only get the interesting parts.
     */
    private function getStacktrace(): string
    {
        ob_start();
        @debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace = ob_get_contents();
        ob_end_clean();

        if ($trace === false) {
            return 'Could not get stacktrace';
        }

        // Remove first items from backtrace as it's this function and previous logging functions which is not interesting
        $shortenTrace = preg_replace('/^#[0-4]\s+[^\n]*\n/m', '', $trace);

        if ($shortenTrace === null) {
            return $trace;
        }

        // Renumber backtrace items.
        $renumberedTrace = preg_replace_callback('/^#(\d+)/m', fn ($matches) => '#' . ((int) $matches[1] - 5), $shortenTrace);

        if ($renumberedTrace === null) {
            return $shortenTrace;
        }

        return $renumberedTrace;
    }
}
