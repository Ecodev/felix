<?php

declare(strict_types=1);

namespace Ecodev\Felix\Middleware;

use Cake\Chronos\Chronos;
use Ecodev\Felix\Validator\IPRange;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Validate that the GraphQL query contains a valid signature in the `Authorization` HTTP header.
 *
 * The signature payload is the GraphQL operation (or operations in case of batching). That means that the query itself
 * and the variables are signed. But it specifically does **not** include uploaded files.
 *
 * The signature is valid for a limited time only, ~15 minutes.
 *
 * The signature syntax is:
 *
 * ```ebnf
 * signature = "v1", ".", timestamp, ".", hash
 * timestamp = current unix time
 * hash = HMAC_SHA256( payload )
 * payload = timestamp, graphql operations
 * ```
 */
final class SignedQueryMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly array $keys,
        private readonly array $allowedIps,
        private readonly bool $required = true
    ) {
        if ($this->required && !$this->keys) {
            throw new Exception('Signed queries are required, but no keys are configured');
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->required) {
            $this->verify($request);
        }

        return $handler->handle($request);
    }

    private function verify(ServerRequestInterface $request): void
    {
        $autorization = $request->getHeader('authorization')[0] ?? '';
        if (!$autorization) {
            if ($this->isAllowedIp($request)) {
                return;
            }

            throw new Exception('Missing `Authorization` HTTP header in signed query', 403);
        }

        if (preg_match('~^v1\.(?<timestamp>\d{10})\.(?<hash>[0-9a-f]{64})$~', $autorization, $m)) {
            $timestamp = $m['timestamp'];
            $hash = $m['hash'];

            $this->verifyTimestamp($timestamp);
            $this->verifyHash($request, $timestamp, $hash);
        } else {
            throw new Exception('Invalid `Authorization` HTTP header in signed query', 403);
        }
    }

    private function verifyTimestamp(string $timestamp): void
    {
        $now = Chronos::now()->timestamp;
        $leeway = 15 * 900; // 15 minutes
        $past = $now - $leeway;
        $future = $now + $leeway;
        if ($timestamp < $past || $timestamp > $future) {
            throw new Exception('Signed query is expired', 403);
        }
    }

    private function verifyHash(ServerRequestInterface $request, string $timestamp, string $hash): void
    {
        $operations = $this->getOperations($request);
        $payload = $timestamp . $operations;

        foreach ($this->keys as $key) {
            $computedHash = hash_hmac('sha256', $payload, $key);
            if ($hash === $computedHash) {
                return;
            }
        }

        throw new Exception('Invalid signed query', 403);
    }

    private function getOperations(ServerRequestInterface $request): mixed
    {
        $contents = $request->getBody()->getContents();
        if ($contents) {
            return $contents;
        }

        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody)) {
            $operations = $parsedBody['operations'] ?? null;
            if ($operations) {
                return $operations;
            }
        }

        throw new Exception('Could not find GraphQL operations in request', 403);
    }

    private function isAllowedIp(ServerRequestInterface $request): bool
    {
        $remoteAddress = $request->getServerParams()['REMOTE_ADDR'] ?? '';

        if (!$remoteAddress || !is_string($remoteAddress)) {
            return false;
        }

        foreach ($this->allowedIps as $allowedIp) {
            if (IPRange::matches($remoteAddress, $allowedIp)) {
                return true;
            }
        }

        return false;
    }
}
