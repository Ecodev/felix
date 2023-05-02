<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Handler;

use Doctrine\Persistence\ObjectRepository;
use Ecodev\Felix\Handler\ImageHandler;
use Ecodev\Felix\Model\Image;
use Ecodev\Felix\Service\ImageResizer;
use Exception;
use Laminas\Diactoros\ServerRequest;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ImageHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        // Minimal binary headers to cheat mime detection
        $virtualFileSystem = [
            'image.png' => '',
            'image-100.jpg' => "\xff\xd8\xff\xe0\x00\x10\x4a\x46\x49\x46\x00\x01\x01\x01\x00\x60",
            'image-100.webp' => 'RIFF4<..WEBPVP8',
        ];

        vfsStream::setup('felix', null, $virtualFileSystem);
    }

    public function testWillServeThumbnailJpgByDefault(): void
    {
        $image = $this->createImageMock();
        $repository = $this->createRepositoryMock($image);

        $maxHeight = 100;
        $imageResizer = $this->createMock(ImageResizer::class);
        $imageResizer->expects(self::once())
            ->method('resize')
            ->with($image, $maxHeight, false)
            ->willReturn('vfs://felix/image-100.jpg');

        // A request without accept header
        $request = new ServerRequest();
        $request = $request->withAttribute('maxHeight', $maxHeight);

        $response = $this->handle($repository, $imageResizer, $request);

        self::assertSame('image/jpeg', $response->getHeaderLine('content-type'));
        self::assertSame('16', $response->getHeaderLine('content-length'));
        self::assertSame('max-age=21600', $response->getHeaderLine('cache-control'));
    }

    public function testWillServeThumbnailWebpIfAccepted(): void
    {
        $image = $this->createImageMock();
        $repository = $this->createRepositoryMock($image);

        $maxHeight = 100;
        $imageResizer = $this->createMock(ImageResizer::class);
        $imageResizer->expects(self::once())
            ->method('resize')
            ->with($image, $maxHeight, true)
            ->willReturn('vfs://felix/image-100.webp');

        // A request specifically accepting webp images
        $request = new ServerRequest();
        $request = $request->withAttribute('maxHeight', $maxHeight)
            ->withHeader('accept', 'text/html, image/webp, */*;q=0.8');

        $response = $this->handle($repository, $imageResizer, $request);

        self::assertSame('image/webp', $response->getHeaderLine('content-type'));
        self::assertSame('15', $response->getHeaderLine('content-length'));
        self::assertSame('max-age=21600', $response->getHeaderLine('cache-control'));
    }

    public function testWillServeOriginalWebpIfAccepted(): void
    {
        $image = $this->createImageMock('vfs://felix/image-100.webp');
        $repository = $this->createRepositoryMock($image);

        $imageResizer = $this->createMock(ImageResizer::class);

        // A request specifically accepting webp images
        $request = new ServerRequest();
        $request = $request
            ->withHeader('accept', 'text/html, image/webp, */*;q=0.8');

        $response = $this->handle($repository, $imageResizer, $request);

        self::assertSame('image/webp', $response->getHeaderLine('content-type'));
        self::assertSame('15', $response->getHeaderLine('content-length'));
        self::assertSame('max-age=21600', $response->getHeaderLine('cache-control'));
    }

    public function testWillServeOriginalJpgIfWebpNotAccepted(): void
    {
        $image = $this->createImageMock('vfs://felix/image-100.webp');
        $repository = $this->createRepositoryMock($image);

        $imageResizer = $this->createMock(ImageResizer::class);
        $imageResizer->expects(self::once())
            ->method('webpToJpg')
            ->with($image)
            ->willReturn('vfs://felix/image-100.jpg');

        // A request specifically accepting webp images
        $request = new ServerRequest();

        $response = $this->handle($repository, $imageResizer, $request);

        self::assertSame('image/jpeg', $response->getHeaderLine('content-type'));
        self::assertSame('16', $response->getHeaderLine('content-length'));
        self::assertSame('max-age=21600', $response->getHeaderLine('cache-control'));
    }

    public function testWillErrorIfImageNotFoundInDatabase(): void
    {
        $repository = $this->createRepositoryMock(null);
        $imageResizer = $this->createMock(ImageResizer::class);
        $request = new ServerRequest();

        $response = $this->handle($repository, $imageResizer, $request);
        $this->assertError(['error' => 'Image 0 not found in database'], $response);
    }

    public function testWillErrorIfImageNotFoundOnDisk(): void
    {
        $image = $this->createImageMock('vfs://felix/totally-non-existing-path');
        $repository = $this->createRepositoryMock($image);
        $imageResizer = $this->createMock(ImageResizer::class);
        $request = new ServerRequest();

        $response = $this->handle($repository, $imageResizer, $request);
        $this->assertError(['error' => 'Image for image 0 not found on disk, or not readable'], $response);
    }

    private function assertError(array $expected, ResponseInterface $response): void
    {
        self::assertSame('application/json', $response->getHeaderLine('content-type'));
        self::assertSame('', $response->getHeaderLine('content-length'));
        self::assertSame('', $response->getHeaderLine('cache-control'));
        self::assertSame($expected, json_decode($response->getBody()->getContents(), true));
    }

    private function handle(ObjectRepository $repository, ImageResizer $imageResizer, ServerRequestInterface $request): ResponseInterface
    {
        $handler = new ImageHandler($repository, $imageResizer);

        return $handler->handle($request);
    }

    private function createImageMock(string $path = 'vfs://felix/image.png'): Image
    {
        $image = $this->createMock(Image::class);
        $image->expects(self::once())->method('getPath')->willReturn($path);
        $image->expects(self::atMost(1))->method('getMime')->willReturn(match ($path) {
            'vfs://felix/image.png' => 'image/png',
            'vfs://felix/image-100.jpg' => 'image/jpeg',
            'vfs://felix/image-100.webp' => 'image/webp',
            'vfs://felix/totally-non-existing-path' => '',
            default => throw new Exception('Unsupported :' . $path),
        });

        return $image;
    }

    private function createRepositoryMock(?Image $image): ObjectRepository
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())->method('find')->willReturn($image);

        return $repository;
    }
}
