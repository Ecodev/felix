<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * An image and some information about it.
 */
trait Image
{
    use AbstractFile {
        setFile as abstractFileSetFile;
    }

    protected function getBasePath(): string
    {
        return 'data/images/';
    }

    protected function getAcceptedMimeTypes(): array
    {
        return [
            'image/avif',
            'image/bmp',
            'image/x-ms-bmp',
            'image/gif',
            'image/heic',
            'image/heif',
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/svg+xml',
            'image/webp',
        ];
    }

    #[ORM\Column(type: 'integer')]
    private int $width = 0;

    #[ORM\Column(type: 'integer')]
    private int $height = 0;

    /**
     * Get image width.
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Set image width.
     */
    #[API\Exclude]
    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    /**
     * Get image height.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Set image height.
     */
    #[API\Exclude]
    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    /**
     * Set the file.
     */
    public function setFile(UploadedFileInterface $file): void
    {
        $this->abstractFileSetFile($file);
        $this->readFileInfo();
    }

    /**
     * Read dimension and size from file on disk.
     */
    private function readFileInfo(): void
    {
        global $container;
        $path = $this->getPath();

        /** @var ImagineInterface $imagine */
        $imagine = $container->get(ImagineInterface::class);
        $image = $imagine->open($path);

        $size = $image->getSize();
        $maxSize = 3500; // Maximum size ever of an image is a bit less than 4K
        $tooBig = $size->getWidth() > $maxSize || $size->getHeight() > $maxSize;

        // Pretty much only SVG is better than WebP
        // We lose PNG animation, even though WebP supports it, but we assume we never use animated PNG anyway
        $worseThanWebp = !in_array($this->getMime(), [
            'image/webp',
            'image/svg+xml',
        ], true);

        if ($tooBig || $worseThanWebp) {
            // Auto-rotate image if EXIF says it's rotated, but only JPG, otherwise it might deteriorate other format (SVG)
            if ($this->getMime() === 'image/jpeg') {
                $autorotate = new Autorotate();
                $autorotate->apply($image);
            }

            $image = $image->thumbnail(new Box($maxSize, $maxSize));
            $size = $image->getSize();

            // Replace extension
            if ($worseThanWebp) {
                $this->setFilename(preg_replace('~((\\.[^.]+)?$)~', '', $this->getFilename()) . '.webp');
                $newPath = $this->getPath();
                $image->save($newPath);
                unlink($path);
            } else {
                $image->save($path);
            }

            $this->validateMimeType();
        }

        $this->setWidth($size->getWidth());
        $this->setHeight($size->getHeight());
    }
}
