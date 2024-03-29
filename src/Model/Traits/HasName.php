<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for all objects with a name.
 */
trait HasName
{
    #[ORM\Column(type: 'string', length: 191)]
    private string $name = '';

    /**
     * Constructor.
     */
    public function __construct(string $name = '')
    {
        $this->setName($name);
    }

    /**
     * Set name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
