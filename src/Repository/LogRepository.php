<?php

declare(strict_types=1);

namespace Ecodev\Felix\Repository;

use Cake\Chronos\Chronos;
use Ecodev\Felix\Model\User;
use Laminas\Log\Logger;

interface LogRepository
{
    /**
     * Log message to be used when user log in
     */
    public const LOGIN = 'login';

    /**
     * Log message to be used when user cannot log in
     */
    public const LOGIN_FAILED = 'login failed';

    /**
     * Log message to be used when user change his password
     */
    public const UPDATE_PASSWORD = 'update password';

    /**
     * Log message to be used when a user requests a link to change his password
     */
    public const REQUEST_PASSWORD_RESET = 'request password reset';

    /**
     * Log message to be used when user cannot change his password
     */
    public const UPDATE_PASSWORD_FAILED = 'update password failed';

    /**
     * Log message to be used when a new user is trying to register (token sent)
     */
    public const REGISTER = 'register';

    /**
     * Log message to be used when a new user account is confirmed (valid token)
     */
    public const REGISTER_CONFIRM = 'confirm registration';

    /**
     * Log message to be used when trying to send email but it's already running
     */
    public const MAILER_LOCKED = 'Unable to obtain lock for mailer, try again later.';

    /**
     * This should NOT be called directly, instead use `_log()` to log stuff
     */
    public function log(array $event): void;

    /**
     * Returns whether the current IP often failed to login
     */
    public function loginFailedOften(): bool;

    public function updatePasswordFailedOften(): bool;

    public function requestPasswordResetOften(): bool;

    public function registerOften(): bool;

    /**
     * Delete log entries which are errors/warnings and older than one month
     * We always keep Logger::INFO level because we use it for statistics
     *
     * @return int the count deleted logs
     */
    public function deleteOldLogs(): int;

    public function getLoginDate(User $user, bool $first): ?Chronos;
}
