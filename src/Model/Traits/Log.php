<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;

/**
 * Log.
 */
trait Log
{
    use HasUrl;

    /**
     * @ORM\Column(type="smallint")
     */
    private int $priority;

    /**
     * @ORM\Column(type="string", length=5000, nullable=false)
     */
    private string $message = '';

    /**
     * @ORM\Column(type="string", length=500, nullable=false)
     */
    private string $referer = '';

    /**
     * @ORM\Column(type="string", length=1000, nullable=false)
     */
    private string $request = '';

    /**
     * @ORM\Column(type="string", length=40, nullable=false)
     */
    private string $ip = '';

    /**
     * The statistics data.
     *
     * @API\Exclude
     * @ORM\Column(type="json", options={"default" = "[]"})
     */
    private array $extra = [];

    /**
     * Set priority.
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Get priority.
     */
    public function getPriority(): int
    {
        return $this->priority;
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
     * @API\Exclude
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @API\Exclude
     */
    public function setExtra(array $extra): void
    {
        $this->extra = $extra;
    }
}
