<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Cake\Chronos\Chronos;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use GraphQL\Doctrine\Annotation as API;

/**
 * Trait for a user with a password and password reset capabilities
 */
trait HasPassword
{
    /**
     * @var string
     *
     * @API\Exclude
     *
     * @ORM\Column(type="string", length=255)
     */
    private $password = '';

    /**
     * @var null|string
     *
     * @API\Exclude
     *
     * @ORM\Column(type="string", length=32, nullable=true, unique=true)
     */
    private $token;

    /**
     * The time when user asked to reset password
     *
     * @var null|Chronos
     *
     * @API\Exclude
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $tokenCreationDate;

    /**
     * Hash and change the user password
     */
    public function setPassword(string $password): void
    {
        // Ignore empty password that could be sent "by mistake" by the client
        // when agreeing to terms
        if ($password === '') {
            return;
        }

        $this->revokeToken();

        $password = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($password)) {
            throw new Exception('Could not hash password');
        }

        $this->password = $password;
    }

    /**
     * Returns the hashed password
     *
     * @API\Exclude
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Generate a new random token to reset password
     */
    public function createToken(): string
    {
        $this->token = bin2hex(random_bytes(16));
        $this->tokenCreationDate = new Chronos();

        return $this->token;
    }

    /**
     * Destroy existing token
     */
    public function revokeToken(): void
    {
        $this->token = null;
        $this->tokenCreationDate = null;
    }

    /**
     * Check if token is valid.
     *
     * @API\Exclude
     */
    public function isTokenValid(): bool
    {
        if (!$this->tokenCreationDate) {
            return false;
        }

        $timeLimit = $this->tokenCreationDate->addMinutes(30);

        return $timeLimit->isFuture();
    }
}
