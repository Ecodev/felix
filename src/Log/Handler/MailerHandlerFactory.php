<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log\Handler;

use Ecodev\Felix\Log\RecordCompleter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

final class MailerHandlerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ?MailerHandler
    {
        /** @var array $config */
        $config = $container->get('config');

        $emailsTo = $config['log']['emails'] ?? [];
        if (!$emailsTo) {
            return null;
        }

        $hostname = $config['hostname'];
        $emailFrom = $config['email']['from'];

        $email = new Email();
        $email->subject("%level_name% on $hostname: %extra.login%: %message%");
        $email->addFrom(...(array) $emailFrom);
        $email->addTo(...(array) $emailsTo);

        /** @var TransportInterface $transport */
        $transport = $container->get(TransportInterface::class);
        /** @var RecordCompleter $recordCompleter */
        $recordCompleter = $container->get(RecordCompleter::class);

        $mailerHandler = new MailerHandler($transport, $email);
        $mailerHandler->pushProcessor($recordCompleter);

        return $mailerHandler;
    }
}
