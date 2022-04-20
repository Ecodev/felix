<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Cake\Chronos\Chronos;
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;

/**
 * A message sent to a user.
 */
trait Message
{
    /**
     * @ORM\Column(type="string", length=191)
     */
    private string $email = '';

    /**
     * @ORM\Column(type="MessageType")
     */
    private string $type;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?Chronos $dateSent = null;

    /**
     * @ORM\Column(type="string", length=255, options={"default" = ""})
     */
    private string $subject = '';

    /**
     * @ORM\Column(type="text", length=65535, options={"default" = ""})
     */
    private string $body = '';

    /**
     * Set type.
     *
     * @API\Input(type="MessageType")
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get type.
     *
     * @API\Field(type="MessageType")
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Get sent time.
     */
    public function getDateSent(): ?Chronos
    {
        return $this->dateSent;
    }

    /**
     * Set sent time.
     *
     * @API\Exclude
     */
    public function setDateSent(?Chronos $dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    /**
     * Recipient email address.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Recipient email address.
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
