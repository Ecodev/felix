<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;

/**
 * Log.
 */
trait Log
{
    use HasUrl;

    #[ORM\Column(type: 'smallint')]
    private int $level;

    #[ORM\Column(type: 'string', length: 5000)]
    private string $message = '';

    #[ORM\Column(type: 'string', length: 500, options: ['default' => ''])]
    private string $referer = '';

    #[ORM\Column(type: 'string', length: 1000, options: ['default' => ''])]
    private string $request = '';

    #[ORM\Column(type: 'string', length: 40, options: ['default' => ''])]
    private string $ip = '';

    /**
     * The data submitted when calling `_log()->info()`.
     */
    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    #[API\Exclude]
    private array $context = [];

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Set message.
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Get message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set referer.
     */
    public function setReferer(string $referer): void
    {
        $this->referer = $referer;
    }

    /**
     * Get referer.
     */
    public function getReferer(): string
    {
        return $this->referer;
    }

    /**
     * Set request.
     */
    public function setRequest(string $request): void
    {
        $this->request = $request;
    }

    /**
     * Get request.
     */
    public function getRequest(): string
    {
        return $this->request;
    }

    /**
     * Set ip.
     */
    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    /**
     * Get ip.
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * The data submitted when calling `_log()->info()`.
     */
    #[API\Exclude]
    public function getContext(): array
    {
        return $this->context;
    }

    #[API\Exclude]
    public function setContext(array $context): void
    {
        $this->context = $context;
    }
}
