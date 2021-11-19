<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log\Writer;

use Ecodev\Felix\Log\EventCompleter;
use Ecodev\Felix\Log\Filter\NoMail;
use Ecodev\Felix\Log\Formatter\Extras;
use Interop\Container\ContainerInterface;
use Laminas\Log\Logger;
use Laminas\Log\Writer\WriterInterface;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

final class MailFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ?WriterInterface
    {
        /** @var array $config */
        $config = $container->get('config');

        $emailsTo = $config['log']['emails'] ?? [];
        if (!$emailsTo) {
            return null;
        }

        $hostname = $config['hostname'];
        $emailFrom = $config['email']['from'];

        $mail = new Message();
        $mail->setFrom($emailFrom)
            ->addTo($emailsTo);

        /** @var TransportInterface $transport */
        $transport = $container->get(TransportInterface::class);
        /** @var EventCompleter $eventCompleter */
        $eventCompleter = $container->get(EventCompleter::class);
        /** @var Extras $formatter */
        $formatter = $container->get(Extras::class);

        $writerEmail = new Mail($mail, $transport, $eventCompleter);

        $writerEmail->setFormatter($formatter);

        // Set subject text for use; summary of number of errors is appended to the
        // subject line before sending the message.
        $writerEmail->setSubjectPrependText('Errors on ' . $hostname);

        // Only email error level entries and higher
        $writerEmail->addFilter(Logger::ERR);
        $writerEmail->addFilter(new NoMail());

        return $writerEmail;
    }
}
