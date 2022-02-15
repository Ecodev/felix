<?php

declare(strict_types=1);

namespace Ecodev\Felix\Handler;

use Doctrine\Persistence\ObjectRepository;
use Ecodev\Felix\Model\File;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FileHandler extends AbstractHandler
{
    private ObjectRepository $fileRepository;

    public function __construct(ObjectRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * Serve a downloaded file from disk.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getAttribute('id');

        /** @var null|File $file */
        $file = $this->fileRepository->find($id);
        if (!$file) {
            return $this->createError("File $id not found in database");
        }

        $path = $file->getPath();
        if (!is_readable($path)) {
            return $this->createError("File for $id not found on disk, or not readable");
        }

        $resource = fopen($path, 'rb');
        if ($resource === false) {
            return $this->createError("Cannot open file for $id on disk");
        }
        $size = filesize($path);
        $type = mime_content_type($path);
        $response = new Response($resource, 200, ['content-type' => $type, 'content-length' => $size]);

        return $response;
    }
}
