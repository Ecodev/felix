<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Doctrine\ORM\Mapping as ORM;

trait HasInternalRemarks
{
    #[ORM\Column(type: 'text', length: 65535)]
    private string $internalRemarks = '';

    /**
     * Set internalRemarks.
     */
    public function setInternalRemarks(string $internalRemarks): void
    {
        $this->internalRemarks = $internalRemarks;
    }

    /**
     * Get internalRemarks.
     */
    public function getInternalRemarks(): string
    {
        return $this->internalRemarks;
    }
}
