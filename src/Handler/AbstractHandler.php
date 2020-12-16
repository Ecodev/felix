<?php

declare(strict_types=1);

namespace Ecodev\Felix\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractHandler implements RequestHandlerInterface
{
    protected function createError(string $message): ResponseInterface
    {
        $response = new JsonResponse(['error' => $message]);

        return $response->withStatus(404, $message);
    }
}
