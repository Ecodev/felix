<?php

declare(strict_types=1);

namespace Ecodev\Felix\Middleware;

use Cake\Chronos\Chronos;
use Ecodev\Felix\Validator\IPRange;
use Exception;
use Laminas\Diactoros\CallbackStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Validate that the GraphQL query contains a valid signature in the `X-Signature` HTTP header.
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
        private readonly bool $required = true,
    ) {
        if ($this->required && !$this->keys) {
            throw new Exception('Signed queries are required, but no keys are configured');
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->required) {
            $request = $this->verify($request);
        }

        return $handler->handle($request);
    }

    private function verify(ServerRequestInterface $request): ServerRequestInterface
    {
        $signature = $request->getHeader('X-Signature')[0] ?? '';
        if (!$signature) {
            if ($this->isAllowedIp($request) || $this->isBingBot($request)) {
                return $request;
            }

            throw new Exception('Missing `X-Signature` HTTP header in signed query', 403);
        }

        if (preg_match('~^v1\.(?<timestamp>\d{10})\.(?<hash>[0-9a-f]{64})$~', $signature, $m)) {
            $timestamp = $m['timestamp'];
            $hash = $m['hash'];

            $this->verifyTimestamp($request, $timestamp);

            return $this->verifyHash($request, $timestamp, $hash);
        }

        throw new Exception('Invalid `X-Signature` HTTP header in signed query', 403);
    }

    private function verifyTimestamp(ServerRequestInterface $request, string $timestamp): void
    {
        $now = Chronos::now()->timestamp;
        $leeway = 15 * 900; // 15 minutes
        $past = $now - $leeway;
        $future = $now + $leeway;
        $isExpired = $timestamp < $past || $timestamp > $future;
        if ($isExpired && !$this->isGoogleBot($request)) {
            throw new Exception('Signed query is expired', 403);
        }
    }

    private function verifyHash(ServerRequestInterface $request, string $timestamp, string $hash): ServerRequestInterface
    {
        ['request' => $request, 'operations' => $operations] = $this->getOperations($request);
        $payload = $timestamp . $operations;

        foreach ($this->keys as $key) {
            $computedHash = hash_hmac('sha256', $payload, $key);
            if ($hash === $computedHash) {
                return $request;
            }
        }

        throw new Exception('Invalid signed query', 403);
    }

    /**
     * @return array{request: ServerRequestInterface, operations: string}
     */
    private function getOperations(ServerRequestInterface $request): array
    {
        $contents = $request->getBody()->getContents();

        if ($contents) {
            return [
                // Pseudo-rewind the request, even if non-rewindable, so the next
                // middleware still accesses the stream from the beginning
                'request' => $request->withBody(new CallbackStream(fn () => $contents)),
                'operations' => $contents,
            ];
        }

        $parsedBody = $request->getParsedBody();

        return [
            'request' => $request,
            'operations' => $parsedBody['operations'] ?? '',
        ];
    }

    private function isAllowedIp(ServerRequestInterface $request): bool
    {
        $remoteAddress = $request->getServerParams()['REMOTE_ADDR'] ?? '';

        if (!$remoteAddress || !is_string($remoteAddress)) {
            return false;
        }

        return IPRange::matches($remoteAddress, $this->allowedIps);
    }

    private function isGoogleBot(ServerRequestInterface $request): bool
    {
        $remoteAddress = $request->getServerParams()['REMOTE_ADDR'] ?? '';

        if (!$remoteAddress || !is_string($remoteAddress)) {
            return false;
        }

        // Source is https://developers.google.com/search/apis/ipranges/googlebot.json
        // Use this one-line command to fetch new list:
        //
        // ```bash
        // php -r 'echo PHP_EOL . implode(PHP_EOL, array_map(fn (array $r) => chr(39) . ($r["ipv6Prefix"] ?? $r["ipv4Prefix"]) . chr(39) . ",", json_decode(file_get_contents("https://developers.google.com/search/apis/ipranges/googlebot.json"), true)["prefixes"])) . PHP_EOL;'
        // ```
        $googleBotIps = [
            '2001:4860:4801:10::/64',
            '2001:4860:4801:11::/64',
            '2001:4860:4801:12::/64',
            '2001:4860:4801:13::/64',
            '2001:4860:4801:14::/64',
            '2001:4860:4801:15::/64',
            '2001:4860:4801:16::/64',
            '2001:4860:4801:17::/64',
            '2001:4860:4801:18::/64',
            '2001:4860:4801:19::/64',
            '2001:4860:4801:1a::/64',
            '2001:4860:4801:1b::/64',
            '2001:4860:4801:1c::/64',
            '2001:4860:4801:1d::/64',
            '2001:4860:4801:1e::/64',
            '2001:4860:4801:1f::/64',
            '2001:4860:4801:20::/64',
            '2001:4860:4801:21::/64',
            '2001:4860:4801:22::/64',
            '2001:4860:4801:23::/64',
            '2001:4860:4801:24::/64',
            '2001:4860:4801:25::/64',
            '2001:4860:4801:26::/64',
            '2001:4860:4801:27::/64',
            '2001:4860:4801:28::/64',
            '2001:4860:4801:29::/64',
            '2001:4860:4801:2::/64',
            '2001:4860:4801:2a::/64',
            '2001:4860:4801:2b::/64',
            '2001:4860:4801:2c::/64',
            '2001:4860:4801:2d::/64',
            '2001:4860:4801:2e::/64',
            '2001:4860:4801:2f::/64',
            '2001:4860:4801:30::/64',
            '2001:4860:4801:31::/64',
            '2001:4860:4801:32::/64',
            '2001:4860:4801:33::/64',
            '2001:4860:4801:34::/64',
            '2001:4860:4801:35::/64',
            '2001:4860:4801:36::/64',
            '2001:4860:4801:37::/64',
            '2001:4860:4801:38::/64',
            '2001:4860:4801:39::/64',
            '2001:4860:4801:3a::/64',
            '2001:4860:4801:3b::/64',
            '2001:4860:4801:3c::/64',
            '2001:4860:4801:3d::/64',
            '2001:4860:4801:3e::/64',
            '2001:4860:4801:3f::/64',
            '2001:4860:4801:40::/64',
            '2001:4860:4801:41::/64',
            '2001:4860:4801:42::/64',
            '2001:4860:4801:43::/64',
            '2001:4860:4801:44::/64',
            '2001:4860:4801:45::/64',
            '2001:4860:4801:46::/64',
            '2001:4860:4801:47::/64',
            '2001:4860:4801:48::/64',
            '2001:4860:4801:49::/64',
            '2001:4860:4801:4a::/64',
            '2001:4860:4801:4b::/64',
            '2001:4860:4801:4c::/64',
            '2001:4860:4801:4d::/64',
            '2001:4860:4801:50::/64',
            '2001:4860:4801:51::/64',
            '2001:4860:4801:52::/64',
            '2001:4860:4801:53::/64',
            '2001:4860:4801:54::/64',
            '2001:4860:4801:55::/64',
            '2001:4860:4801:56::/64',
            '2001:4860:4801:57::/64',
            '2001:4860:4801:60::/64',
            '2001:4860:4801:61::/64',
            '2001:4860:4801:62::/64',
            '2001:4860:4801:63::/64',
            '2001:4860:4801:64::/64',
            '2001:4860:4801:65::/64',
            '2001:4860:4801:66::/64',
            '2001:4860:4801:67::/64',
            '2001:4860:4801:68::/64',
            '2001:4860:4801:69::/64',
            '2001:4860:4801:6a::/64',
            '2001:4860:4801:6b::/64',
            '2001:4860:4801:6c::/64',
            '2001:4860:4801:6d::/64',
            '2001:4860:4801:6e::/64',
            '2001:4860:4801:6f::/64',
            '2001:4860:4801:70::/64',
            '2001:4860:4801:71::/64',
            '2001:4860:4801:72::/64',
            '2001:4860:4801:73::/64',
            '2001:4860:4801:74::/64',
            '2001:4860:4801:75::/64',
            '2001:4860:4801:76::/64',
            '2001:4860:4801:77::/64',
            '2001:4860:4801:78::/64',
            '2001:4860:4801:79::/64',
            '2001:4860:4801:7a::/64',
            '2001:4860:4801:7b::/64',
            '2001:4860:4801:80::/64',
            '2001:4860:4801:81::/64',
            '2001:4860:4801:82::/64',
            '2001:4860:4801:83::/64',
            '2001:4860:4801:84::/64',
            '2001:4860:4801:85::/64',
            '2001:4860:4801:86::/64',
            '2001:4860:4801:87::/64',
            '2001:4860:4801:88::/64',
            '2001:4860:4801:90::/64',
            '2001:4860:4801:91::/64',
            '2001:4860:4801:92::/64',
            '2001:4860:4801:93::/64',
            '2001:4860:4801:94::/64',
            '2001:4860:4801:95::/64',
            '2001:4860:4801:96::/64',
            '2001:4860:4801:97::/64',
            '2001:4860:4801:a0::/64',
            '2001:4860:4801:a1::/64',
            '2001:4860:4801:a2::/64',
            '2001:4860:4801:a3::/64',
            '2001:4860:4801:a4::/64',
            '2001:4860:4801:a5::/64',
            '2001:4860:4801:a6::/64',
            '2001:4860:4801:a7::/64',
            '2001:4860:4801:a8::/64',
            '2001:4860:4801:a9::/64',
            '2001:4860:4801:aa::/64',
            '2001:4860:4801:ab::/64',
            '2001:4860:4801:ac::/64',
            '2001:4860:4801:b0::/64',
            '2001:4860:4801:b1::/64',
            '2001:4860:4801:b2::/64',
            '2001:4860:4801:b3::/64',
            '2001:4860:4801:b4::/64',
            '2001:4860:4801:c::/64',
            '2001:4860:4801:f::/64',
            '192.178.4.0/27',
            '192.178.4.128/27',
            '192.178.4.160/27',
            '192.178.4.32/27',
            '192.178.4.64/27',
            '192.178.4.96/27',
            '192.178.5.0/27',
            '192.178.6.0/27',
            '192.178.6.128/27',
            '192.178.6.160/27',
            '192.178.6.192/27',
            '192.178.6.224/27',
            '192.178.6.32/27',
            '192.178.6.64/27',
            '192.178.6.96/27',
            '192.178.7.0/27',
            '192.178.7.128/27',
            '192.178.7.160/27',
            '192.178.7.32/27',
            '192.178.7.64/27',
            '192.178.7.96/27',
            '34.100.182.96/28',
            '34.101.50.144/28',
            '34.118.254.0/28',
            '34.118.66.0/28',
            '34.126.178.96/28',
            '34.146.150.144/28',
            '34.147.110.144/28',
            '34.151.74.144/28',
            '34.152.50.64/28',
            '34.154.114.144/28',
            '34.155.98.32/28',
            '34.165.18.176/28',
            '34.175.160.64/28',
            '34.176.130.16/28',
            '34.22.85.0/27',
            '34.64.82.64/28',
            '34.65.242.112/28',
            '34.80.50.80/28',
            '34.88.194.0/28',
            '34.89.10.80/28',
            '34.89.198.80/28',
            '34.96.162.48/28',
            '35.247.243.240/28',
            '66.249.64.0/27',
            '66.249.64.128/27',
            '66.249.64.160/27',
            '66.249.64.192/27',
            '66.249.64.224/27',
            '66.249.64.32/27',
            '66.249.64.64/27',
            '66.249.64.96/27',
            '66.249.65.0/27',
            '66.249.65.128/27',
            '66.249.65.160/27',
            '66.249.65.192/27',
            '66.249.65.224/27',
            '66.249.65.32/27',
            '66.249.65.64/27',
            '66.249.65.96/27',
            '66.249.66.0/27',
            '66.249.66.128/27',
            '66.249.66.160/27',
            '66.249.66.192/27',
            '66.249.66.224/27',
            '66.249.66.32/27',
            '66.249.66.64/27',
            '66.249.66.96/27',
            '66.249.67.0/27',
            '66.249.68.0/27',
            '66.249.68.128/27',
            '66.249.68.160/27',
            '66.249.68.32/27',
            '66.249.68.64/27',
            '66.249.68.96/27',
            '66.249.69.0/27',
            '66.249.69.128/27',
            '66.249.69.160/27',
            '66.249.69.192/27',
            '66.249.69.224/27',
            '66.249.69.32/27',
            '66.249.69.64/27',
            '66.249.69.96/27',
            '66.249.70.0/27',
            '66.249.70.128/27',
            '66.249.70.160/27',
            '66.249.70.192/27',
            '66.249.70.224/27',
            '66.249.70.32/27',
            '66.249.70.64/27',
            '66.249.70.96/27',
            '66.249.71.0/27',
            '66.249.71.128/27',
            '66.249.71.160/27',
            '66.249.71.192/27',
            '66.249.71.224/27',
            '66.249.71.32/27',
            '66.249.71.64/27',
            '66.249.71.96/27',
            '66.249.72.0/27',
            '66.249.72.128/27',
            '66.249.72.160/27',
            '66.249.72.192/27',
            '66.249.72.224/27',
            '66.249.72.32/27',
            '66.249.72.64/27',
            '66.249.72.96/27',
            '66.249.73.0/27',
            '66.249.73.128/27',
            '66.249.73.160/27',
            '66.249.73.192/27',
            '66.249.73.224/27',
            '66.249.73.32/27',
            '66.249.73.64/27',
            '66.249.73.96/27',
            '66.249.74.0/27',
            '66.249.74.128/27',
            '66.249.74.160/27',
            '66.249.74.192/27',
            '66.249.74.224/27',
            '66.249.74.32/27',
            '66.249.74.64/27',
            '66.249.74.96/27',
            '66.249.75.0/27',
            '66.249.75.128/27',
            '66.249.75.160/27',
            '66.249.75.192/27',
            '66.249.75.224/27',
            '66.249.75.32/27',
            '66.249.75.64/27',
            '66.249.75.96/27',
            '66.249.76.0/27',
            '66.249.76.128/27',
            '66.249.76.160/27',
            '66.249.76.192/27',
            '66.249.76.224/27',
            '66.249.76.32/27',
            '66.249.76.64/27',
            '66.249.76.96/27',
            '66.249.77.0/27',
            '66.249.77.128/27',
            '66.249.77.160/27',
            '66.249.77.192/27',
            '66.249.77.224/27',
            '66.249.77.32/27',
            '66.249.77.64/27',
            '66.249.77.96/27',
            '66.249.78.0/27',
            '66.249.78.32/27',
            '66.249.78.64/27',
            '66.249.78.96/27',
            '66.249.79.0/27',
            '66.249.79.128/27',
            '66.249.79.160/27',
            '66.249.79.192/27',
            '66.249.79.224/27',
            '66.249.79.32/27',
            '66.249.79.64/27',
            '66.249.79.96/27',
        ];

        return IPRange::matches($remoteAddress, $googleBotIps);
    }

    private function isBingBot(ServerRequestInterface $request): bool
    {
        $remoteAddress = $request->getServerParams()['REMOTE_ADDR'] ?? '';

        if (!$remoteAddress || !is_string($remoteAddress)) {
            return false;
        }

        // Source is https://www.bing.com/toolbox/bingbot.json
        // Use this one-line command to fetch new list:
        //
        // ```bash
        // php -r 'echo PHP_EOL . implode(PHP_EOL, array_map(fn (array $r) => chr(39) . ($r["ipv6Prefix"] ?? $r["ipv4Prefix"]) . chr(39) . ",", json_decode(file_get_contents("https://www.bing.com/toolbox/bingbot.json"), true)["prefixes"])) . PHP_EOL;'
        // ```
        $bingBotIps = [
            '157.55.39.0/24',
            '207.46.13.0/24',
            '40.77.167.0/24',
            '13.66.139.0/24',
            '13.66.144.0/24',
            '52.167.144.0/24',
            '13.67.10.16/28',
            '13.69.66.240/28',
            '13.71.172.224/28',
            '139.217.52.0/28',
            '191.233.204.224/28',
            '20.36.108.32/28',
            '20.43.120.16/28',
            '40.79.131.208/28',
            '40.79.186.176/28',
            '52.231.148.0/28',
            '20.79.107.240/28',
            '51.105.67.0/28',
            '20.125.163.80/28',
            '40.77.188.0/22',
            '65.55.210.0/24',
            '199.30.24.0/23',
            '40.77.202.0/24',
            '40.77.139.0/25',
            '20.74.197.0/28',
            '20.15.133.160/27',
            '40.77.177.0/24',
            '40.77.178.0/23',
        ];

        return IPRange::matches($remoteAddress, $bingBotIps);
    }
}
