<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Blog\Model;

use Doctrine\ORM\Mapping as ORM;
use Ecodev\Felix\Model\HasOwner;
use Ecodev\Felix\Model\Model;

/**
 * Base class for all objects stored in database.
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractModel implements HasOwner, Model
{
    /**
     * @ORM\Column(type="integer", options={"unsigned" = true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected ?int $id = null;

    private ?User $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?\Ecodev\Felix\Model\User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
    }
}
