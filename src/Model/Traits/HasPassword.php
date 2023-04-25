<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Cake\Chronos\Chronos;
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;

/**
 * Trait for a user with a password and password reset capabilities.
 */
trait HasPassword
{
    #[ORM\Column(type: 'string', length: 255)]
    #[API\Exclude]
    private string $password = '';

    #[ORM\Column(type: 'string', length: 32, nullable: true, unique: true)]
    #[API\Exclude]
    private ?string $token = null;

    /**
     * The time when user asked to reset password.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    #[API\Exclude]
    private ?Chronos $tokenCreationDate = null;

    /**
     * Hash and change the user password.
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

        $this->password = $password;
    }

    /**
     * Returns the hashed password.
     */
    #[API\Exclude]
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Generate a new random token to reset password.
     */
    public function createToken(): string
    {
        $this->token = bin2hex(random_bytes(16));
        $this->tokenCreationDate = new Chronos();

        return $this->token;
    }

    /**
     * Destroy existing token.
     */
    public function revokeToken(): void
    {
        $this->token = null;
        $this->tokenCreationDate = null;
    }

    /**
     * Check if token is valid.
     */
    #[API\Exclude]
    public function isTokenValid(): bool
    {
        if (!$this->tokenCreationDate) {
            return false;
        }

        $timeLimit = $this->tokenCreationDate->addMinutes(30);

        return $timeLimit->isFuture();
    }
}
