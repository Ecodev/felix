<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log\Handler;

use GraphQL\Error\Error;
use Monolog\Handler\SymfonyMailerHandler;
use Monolog\LogRecord;

/**
 * Normal mailer handler but ignores exceptions that are explicitly marked as ignored.
 */
class MailerHandler extends SymfonyMailerHandler
{
    public function isHandling(LogRecord $record): bool
    {
        $exception = $record->context['exception'] ?? null;

        $ignored = $exception instanceof NoMailLogging || ($exception instanceof Error && $exception->getPrevious() instanceof NoMailLogging);

        return !$ignored && parent::isHandling($record);
    }
}
