<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class TransportFactory implements FactoryInterface
{
    /**
     * Return a configured mail transport.
     *
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): TransportInterface
    {
        /** @var array $config */
        $config = $container->get('config');

        // Setup SMTP transport, or a mock one
        $configSmtp = $config['smtp'] ?? null;
        $dsn = self::dsn($configSmtp);

        $transport = Transport::fromDsn($dsn);

        return $transport;
    }

    /**
     * Return a DSN for Symfony Mailer from given parameters. If input is empty, then it will default to a DSN for a NullTransport.
     *
     * @param null|array{host?: string, port?: int, user?: ?string, password?: ?string, connection_config?: array{username?: ?string, password?: ?string}}|string $configSmtp
     */
    public static function dsn(null|array|string $configSmtp): string
    {
        if (!$configSmtp) {
            return 'null://null';
        }

        if (is_string($configSmtp)) {
            return $configSmtp;
        }

        $host = urlencode($configSmtp['host'] ?? '');
        $port = $configSmtp['port'] ?? null ?: 587;
        $user = urlencode($configSmtp['user'] ?? $configSmtp['connection_config']['username'] ?? '');
        $password = urlencode($configSmtp['password'] ?? $configSmtp['connection_config']['password'] ?? '');

        if (!$host) {
            return 'null://null';
        }

        $credentials = $user && $password ? "$user:$password@" : '';

        return "smtp://$credentials$host:$port";
    }
}
