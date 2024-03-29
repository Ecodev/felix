<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Model\Traits;

use Ecodev\Felix\Model\Traits\File;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{
    private \Ecodev\Felix\Model\File $file;

    protected function setUp(): void
    {
        $this->file = new class() implements \Ecodev\Felix\Model\File {
            use File;
        };
    }

    public function testGetPath(): void
    {
        $this->file->setFilename('invoice.pdf');

        self::assertSame('invoice.pdf', $this->file->getFilename());
        $appPath = realpath('.');
        $expected = $appPath . '/data/file/invoice.pdf';
        self::assertSame($expected, $this->file->getPath());
    }
}
