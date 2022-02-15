<?php

declare(strict_types=1);

namespace Ecodev\Felix\Handler;

use Doctrine\Persistence\ObjectRepository;
use Ecodev\Felix\Model\Image;
use Ecodev\Felix\Service\ImageResizer;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ImageHandler extends AbstractHandler
{
    private ObjectRepository $imageRepository;

    private ImageResizer $imageResizer;

    public function __construct(ObjectRepository $imageRepository, ImageResizer $imageService)
    {
        $this->imageRepository = $imageRepository;
        $this->imageResizer = $imageService;
    }

    /**
     * Serve an image from disk, with optional dynamic resizing.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getAttribute('id');

        /** @var null|Image $image */
        $image = $this->imageRepository->find($id);
        if (!$image) {
            return $this->createError("Image $id not found in database");
        }

        $path = $image->getPath();
        if (!is_readable($path)) {
            return $this->createError("Image for image $id not found on disk, or not readable");
        }

        $maxHeight = (int) $request->getAttribute('maxHeight');
        if ($maxHeight) {
            $accept = $request->getHeaderLine('accept');
            $useWebp = mb_strpos($accept, 'image/webp') !== false;

            $path = $this->imageResizer->resize($image, $maxHeight, $useWebp);
        }

        $resource = fopen($path, 'rb');
        if ($resource === false) {
            return $this->createError("Cannot open file for image $id on disk");
        }

        $size = filesize($path);
        $type = mime_content_type($path);

        // Be sure that browser show SVG instead of downloading
        if ($type === 'image/svg') {
            $type = 'image/svg+xml';
        }

        $response = new Response($resource, 200, ['content-type' => $type, 'content-length' => $size]);

        return $response;
    }
}
