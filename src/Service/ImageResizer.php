<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Ecodev\Felix\Model\Image;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;

/**
 * Service to resize image's images.
 */
class ImageResizer
{
    private const CACHE_IMAGE_PATH = 'data/cache/images/';

    private ImagineInterface $imagine;

    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }

    /**
     * Resize image as JPG or WEBP and return the path to the resized version.
     */
    public function resize(Image $image, int $maxHeight, bool $useWebp): string
    {
        if ($image->getMime() === 'image/svg+xml') {
            return $image->getPath();
        }

        $maxHeight = min($maxHeight, $image->getHeight());

        $basename = pathinfo($image->getFilename(), PATHINFO_FILENAME);
        $extension = $useWebp ? '.webp' : '.jpg';
        $path = realpath('.') . '/' . self::CACHE_IMAGE_PATH . $basename . '-' . $maxHeight . $extension;

        if (file_exists($path)) {
            return $path;
        }

        $image = $this->imagine->open($image->getPath());
        $image->thumbnail(new Box(1_000_000, $maxHeight))->save($path);

        return $path;
    }
}
