<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Model\Traits;

use Ecodev\Felix\Model\Traits\Image;
use EcodevTests\Felix\Traits\TestWithContainer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

final class ImageTest extends TestCase
{
    private const TEMP = '/tmp/felix';

    use TestWithContainer {
        tearDown as tearDownWithContainer;
    }

    protected function setUp(): void
    {
        @mkdir(self::TEMP, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->tearDownWithContainer();
        shell_exec('rm -rf ' . self::TEMP);
    }

    public function testGetPath(): void
    {
        $image = $this->createImage();
        $image->setFilename('photo.jpg');

        self::assertSame('photo.jpg', $image->getFilename());
        $appPath = realpath('.');
        $expected = $appPath . '/data/images/photo.jpg';
        self::assertSame($expected, $image->getPath());
    }

    public function testDimension(): void
    {
        $image = $this->createImage();
        $image->setWidth(123);
        $image->setHeight(456);

        self::assertSame(123, $image->getWidth());
        self::assertSame(456, $image->getHeight());
    }

    /**
     * @dataProvider providerSetFile
     */
    public function testSetFile(string $filename, int $width, int $height, bool $isSvg = false): void
    {
        $this->createDefaultFelixContainer();

        $file = $this->createFileToUpload($filename);
        $image = $this->createImageForUpload();

        $image->setFile($file);
        self::assertSame($width, $image->getWidth());
        self::assertSame($height, $image->getHeight());
        self::assertSame($isSvg ? 'image/svg+xml' : 'image/webp', $image->getMime());
        self::assertStringEndsWith($isSvg ? '.svg' : '.webp', $image->getPath());
    }

    public static function providerSetFile(): iterable
    {
        yield 'jpg is converted to webp' => ['image.jpg', 400, 400];
        yield 'png is converted to webp' => ['image.png', 400, 300];
        yield 'avif is converted to webp' => ['image.avif', 400, 299];
        yield 'heif is converted to webp' => ['image.heif', 1440, 960];
        yield 'svg is untouched' => ['logo.svg', 445, 488, true];
        yield 'webp is untouched' => ['image.webp', 400, 400];
        yield 'huge jpg is resized to webp' => ['huge.jpg', 3500, 19];
        yield 'huge webp is resized' => ['huge.webp', 3500, 19];
    }

    private function createImage(): \Ecodev\Felix\Model\Image
    {
        return new class() implements \Ecodev\Felix\Model\Image {
            use Image;
        };
    }

    private function createImageForUpload(): \Ecodev\Felix\Model\Image
    {
        return new class() implements \Ecodev\Felix\Model\Image {
            use Image;

            public function getBasePath(): string
            {
                return '/tmp/felix/';
            }

            public function getPath(): string
            {
                return $this->getBasePath() . $this->getFilename();
            }
        };
    }

    private function createFileToUpload(string $filename): UploadedFileInterface
    {
        $file = $this->createMock(UploadedFileInterface::class);
        $file->expects(self::once())
            ->method('getClientFilename')
            ->willReturn("useless-prefix-$filename");

        $file->expects(self::once())
            ->method('moveTo')
            ->willReturnCallback(fn ($dest) => copy("tests/data/images/$filename", $dest));

        return $file;
    }
}
