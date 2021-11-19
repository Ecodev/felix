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
     */
    public function testResize(string $extension, int $wantedHeight, bool $useWebp, string $expected): void
    {
        switch ($extension) {
            case 'png':
                $mime = 'image/png';

                break;
            case 'svg':
                $mime = 'image/svg+xml';

                break;
            case 'tiff':
                $mime = 'image/tiff';

                break;
            default:
                throw new Exception('Unsupported extension: ' . $extension);
        }

        $imagineImage = $this->createMock(ImageInterface::class);
        $imagineImage->expects(self::any())->method('thumbnail')->willReturnSelf();

        $imagine = $this->createMock(ImagineInterface::class);
        $imagine->expects(self::any())->method('open')->willReturn($imagineImage);

        $resizer = new ImageResizer($imagine);
        $image = $this->createMock(Image::class);
        $image->expects(self::once())->method('getPath')->willReturn('/felix/image.' . $extension);
        $image->expects(self::any())->method('getFilename')->willReturn('image.' . $extension);
        $image->expects(self::atMost(1))->method('getHeight')->willReturn(200);
        $image->expects(self::any())->method('getMime')->willReturn($mime);

        $actual = $resizer->resize($image, $wantedHeight, $useWebp);
        self::assertStringEndsWith($expected, $actual);
    }

    public function providerResize(): array
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
}
