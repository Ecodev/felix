<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Service;

use Ecodev\Felix\Service\ImagineFactory;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class ImagineFactoryTest extends TestCase
{
    public function testFactory(): void
    {
        $factory = new ImagineFactory();

        $actual = $factory($this->createMock(ContainerInterface::class), '');
        self::assertInstanceOf(ImagineInterface::class, $actual);

        $image = $actual->create(new Box(10, 10));
        self::assertInstanceOf(ImageInterface::class, $image);
    }
}
