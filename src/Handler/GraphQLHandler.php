<?php

declare(strict_types=1);

namespace Ecodev\Felix\Handler;

use Ecodev\Felix\Api\Server;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GraphQLHandler implements RequestHandlerInterface
{
    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->server->execute($request);

        return new JsonResponse($response);
    }
}
