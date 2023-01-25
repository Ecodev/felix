<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for all objects with an URL.
 */
trait HasUrl
{
    /**
     * @ORM\Column(type="string", length=2000, options={"default" = ""})
     */
    private string $url = '';

    /**
     * Set url.
     *
     * @API\Input(type="Url")
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get url.
     *
     * @API\Field(type="Url")
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
