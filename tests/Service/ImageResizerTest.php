<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Service;

use Ecodev\Felix\Model\Image;
use Ecodev\Felix\Service\ImageResizer;
use Exception;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use PHPUnit\Framework\TestCase;

class ImageResizerTest extends TestCase
{
    /**
     * @dataProvider providerResize
     *
     * @param non-empty-string $expected
     */
    public function testResize(string $extension, int $wantedHeight, bool $useWebp, string $expected): void
    {
        $mime = match ($extension) {
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'tiff' => 'image/tiff',
            default => throw new Exception('Unsupported extension: ' . $extension),
        };

        $imagineImage = $this->createMock(ImageInterface::class);
        $imagineImage->expects(self::atMost(1))->method('thumbnail')->willReturnSelf();

        $imagine = $this->createMock(ImagineInterface::class);
        $imagine->expects(self::atMost(1))->method('open')->willReturn($imagineImage);

        $resizer = new ImageResizer($imagine);
        $image = $this->createMock(Image::class);
        $image->expects(self::once())->method('getPath')->willReturn('/felix/image.' . $extension);
        $image->expects(self::atMost(1))->method('getFilename')->willReturn('image.' . $extension);
        $image->expects(self::atMost(1))->method('getHeight')->willReturn(200);
        $image->expects(self::atMost(1))->method('getMime')->willReturn($mime);

        $actual = $resizer->resize($image, $wantedHeight, $useWebp);
        self::assertStringEndsWith($expected, $actual);
    }

    public static function providerResize(): iterable
    {
        return [
            'png smaller' => ['png', 100, false, 'data/cache/images/image-100.jpg'],
            'png smaller webp' => ['png', 100, true, 'data/cache/images/image-100.webp'],
            'png same' => ['png', 200, false, 'data/cache/images/image-200.jpg'],
            'png same webp' => ['png', 200, true, 'data/cache/images/image-200.webp'],
            'png bigger' => ['png', 300, false, 'data/cache/images/image-200.jpg'],
            'png bigger webp' => ['png', 300, true, 'data/cache/images/image-200.webp'],

            // SVG is never resized
            'svg smaller' => ['svg', 100, false, '/felix/image.svg'],
            'svg smaller webp' => ['svg', 100, true, '/felix/image.svg'],
            'svg same' => ['svg', 200, false, '/felix/image.svg'],
            'svg same webp' => ['svg', 200, true, '/felix/image.svg'],
            'svg bigger' => ['svg', 300, false, '/felix/image.svg'],
            'svg bigger webp' => ['svg', 300, true, '/felix/image.svg'],

            // TIFF is never returned as TIFF
            'tiff smaller' => ['tiff', 100, false, 'data/cache/images/image-100.jpg'],
            'tiff smaller webp' => ['tiff', 100, true, 'data/cache/images/image-100.webp'],
            'tiff same' => ['tiff', 200, false, 'data/cache/images/image-200.jpg'],
            'tiff same webp' => ['tiff', 200, true, 'data/cache/images/image-200.webp'],
            'tiff bigger' => ['tiff', 300, false, 'data/cache/images/image-200.jpg'],
            'tiff bigger webp' => ['tiff', 300, true, 'data/cache/images/image-200.webp'],
        ];
    }

    public function testWebpToJpg(): void
    {
        $imagineImage = $this->createMock(ImageInterface::class);

        $imagine = $this->createMock(ImagineInterface::class);
        $imagine->expects(self::once())->method('open')->willReturn($imagineImage);

        $imageResizer = new ImageResizer($imagine);
        $image = $this->createMock(Image::class);
        $image->expects(self::once())->method('getPath')->willReturn('/felix/image.webp');
        $image->expects(self::once())->method('getFilename')->willReturn('image.webp');

        $actual = $imageResizer->webpToJpg($image);
        self::assertStringEndsWith('data/cache/images/image.jpg', $actual);
    }
}
