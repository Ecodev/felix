<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log\Formatter;

use Laminas\Log\Formatter\Simple;

/**
 * Simple formatter that show the Felix extras fields
 */
final class Extras extends Simple
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $format = '
Time    : %timestamp%
Priority: %priorityName% (%priority%)
Login   : %login%
URL     : %url%
REFERER : %referer%
IP      : %ip%

REQUEST:
%request%

MESSAGE:
%message%

';
        parent::__construct($format);
    }
}
