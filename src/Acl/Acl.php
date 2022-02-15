<?php

declare(strict_types=1);

namespace Ecodev\Felix\Acl;

use Doctrine\Common\Util\ClassUtils;
use Ecodev\Felix\Model\CurrentUser;
use Ecodev\Felix\Model\Model;

class Acl extends \Laminas\Permissions\Acl\Acl
{
    /**
     * The message explaining the last denial.
     *
     * @var null|string
     */
    private $message;

    /**
     * @var string[]
     */
    private $reasons = [];

    protected function createModelResource(string $class): ModelResource
    {
        $resource = new ModelResource($class);
        $this->addResource($resource);

        return $resource;
    }

    /**
     * Return whether the current user is allowed to do something.
     *
     * This should be the main method to do all ACL checks.
     */
    public function isCurrentUserAllowed(Model $model, string $privilege): bool
    {
        $resource = new ModelResource($this->getClass($model), $model);
        $role = $this->getCurrentRole();
        $this->reasons = [];

        $isAllowed = $this->isAllowed($role, $resource, $privilege);

        $this->message = $this->buildMessage($resource, $privilege, $role, $isAllowed);

        return $isAllowed;
    }

    /**
     * Set the reason for rejection that will be shown to end-user.
     *
     * This method always return false for usage convenience and should be used by all assertions,
     * instead of only return false themselves.
     *
     * @return false
     */
    public function reject(string $reason): bool
    {
        $this->reasons[] = $reason;

        return false;
    }

    private function getClass(Model $resource): string
    {
        return ClassUtils::getRealClass(get_class($resource));
    }

    private function getCurrentRole(): string
    {
        $user = CurrentUser::get();
        if (!$user) {
            return 'anonymous';
        }

        return $user->getRole();
    }

    private function buildMessage(ModelResource $resource, ?string $privilege, string $role, bool $isAllowed): ?string
    {
        if ($isAllowed) {
            return null;
        }

        $resource = $resource->getName();

        $user = CurrentUser::get();
        $userName = $user ? 'User "' . $user->getLogin() . '"' : 'Non-logged user';
        $privilege = $privilege === null ? 'NULL' : $privilege;

        $message = "$userName with role $role is not allowed on resource \"$resource\" with privilege \"$privilege\"";

        $count = count($this->reasons);
        if ($count === 1) {
            $message .= ' because ' . $this->reasons[0];
        } elseif ($count) {
            $list = array_map(fn ($reason) => '- ' . $reason, $this->reasons);
            $message .= ' because:' . PHP_EOL . PHP_EOL . implode(PHP_EOL, $list);
        }

        return $message;
    }

    /**
     * Returns the message explaining the last denial, if any.
     */
    public function getLastDenialMessage(): ?string
    {
        return $this->message;
    }
}
