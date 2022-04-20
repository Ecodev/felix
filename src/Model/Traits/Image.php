<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use Imagine\Filter\Basic\Autorotate;
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
            'image/bmp',
            'image/x-ms-bmp',
            'image/gif',
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/svg+xml',
            'image/webp',
        ];
    }

    /**
     * @ORM\Column(type="integer")
     */
    private int $width = 0;

    /**
     * @ORM\Column(type="integer")
     */
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
     *
     * @API\Exclude
     */
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
     *
     * @API\Exclude
     */
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

        // Auto-rotate image if EXIF says it's rotated, but only JPG, otherwise it might deteriorate other format (SVG)
        if ($this->getMime() === 'image/jpeg') {
            $autorotate = new Autorotate();
            $autorotate->apply($image);
            $image->save($path);
        }

        $size = $image->getSize();

        $this->setWidth($size->getWidth());
        $this->setHeight($size->getHeight());
    }
}
