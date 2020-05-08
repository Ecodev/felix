<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model;

interface User extends Model
{
    /**
     * Get full name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the user role
     *
     * @return string
     */
    public function getRole(): string;
}