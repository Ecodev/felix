<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api;

use Ecodev\Felix\Log\Filter\NoMailLogging;

/**
 * Exception that will show its message to end-user even on production server, but
 * will not be sent to developers as emails
 */
class ExceptionWithoutMailLogging extends Exception implements NoMailLogging
{
}
