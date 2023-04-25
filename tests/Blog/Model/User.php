<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Blog\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ecodev\Felix\Acl\MultipleRoles;
use GraphQL\Doctrine\Attribute as API;

/**
 * A blog author.
 */
#[ORM\Entity]
final class User extends AbstractModel implements \Ecodev\Felix\Model\User
{
    #[ORM\Column(name: 'custom_column_name', type: 'string', length: 50, options: ['default' => ''])]
    private string $name = '';

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    #[API\Exclude]
    private string $password;

    #[ORM\OneToMany(targetEntity: 'EcodevTests\Felix\Blog\Model\Post', mappedBy: 'user')]
    private Collection $posts;

    public function __construct(private MultipleRoles|string $role = 'member')
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getRole(): string|MultipleRoles
    {
        return $this->role;
    }

    public function getLogin(): ?string
    {
        return $this->name;
    }
}
