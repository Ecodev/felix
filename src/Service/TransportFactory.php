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
        if ($configSmtp) {
            $dsn = self::dsn(
                $configSmtp['host'],
                $configSmtp['port'],
                $configSmtp['user'],
                $configSmtp['password'],
            );
        } else {
            $dsn = 'null://null';
        }

        $transport = Transport::fromDsn($dsn);

        return $transport;
    }

    public static function dsn(string $host, int $port, string $user, string $password, ?bool $isTls = null): string
    {
        if ($isTls === null) {
            $isTls = $port !== 25;
        }

        $scheme = $isTls ? 'smtp' : 'smtps';
        $credentials = $user && $password ? "$user:$password@" : '';

        return "$scheme://$credentials$host:$port";
    }
}
