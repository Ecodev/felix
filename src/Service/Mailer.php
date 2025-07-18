<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Ecodev\Felix\Model\Message;
use Ecodev\Felix\Repository\LogRepository;
use Ecodev\Felix\Repository\MessageRepository;
use Exception;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Service to send a message as an email.
 */
class Mailer
{
    /**
     * DO NOT REMOVE THIS PROPERTY !
     *
     * When it is garbage collected, the lock will be released.
     * And the lock must only be released at the end of PHP process,
     * never at the end of the method.
     *
     * @var false|resource
     */
    private $lock;

    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly MessageRepository $messageRepository,
        private readonly TransportInterface $transport,
        private readonly string $phpPath,
        private readonly ?string $toEmailOverride,
        private readonly string $fromEmail,
        protected string $fromName,
    ) {}

    /**
     * Send a message asynchronously in a separate process.
     *
     * This should be the preferred way to send a message, unless if we are the cron.
     */
    public function sendMessageAsync(Message $message): void
    {
        // Be sure we have an ID before "forking" process
        if ($message->getId() === null) {
            $this->entityManager->flush();
        }

        $args = [
            (string) realpath('bin/send-message.php'),
            (string) $message->getId(),
        ];

        $escapedArgs = array_map('escapeshellarg', $args);

        $cmd = escapeshellcmd($this->phpPath) . ' ' . implode(' ', $escapedArgs) . ' > /dev/null 2>&1 &';
        exec($cmd);
    }

    /**
     * Send a message.
     */
    public function sendMessage(Message $message): void
    {
        $mailMessage = $this->messageToEmail($message);

        $email = $message->getEmail();
        $overriddenBy = '';
        if ($this->toEmailOverride) {
            $email = $this->toEmailOverride;
            $overriddenBy = ' overridden by ' . $email;
        }

        $recipient = $message->getRecipient();
        $recipientName = $recipient?->getName() ?: '';
        if ($email) {
            $mailMessage->addTo(new Address($email, $recipientName));
            $this->transport->send($mailMessage);
        }

        $message->setDateSent(new Chronos());
        $this->entityManager->flush();

        $addressList = $mailMessage->getFrom();
        if ($addressList) {
            echo 'email from ' . $addressList[0]->getAddress() . ' sent to: ' . $message->getEmail() . "\t" . $overriddenBy . "\t" . $message->getSubject() . PHP_EOL;
        }
    }

    /**
     * Convert our model message to an email.
     */
    protected function messageToEmail(Message $message): Email
    {
        $email = new Email();
        $email->subject($message->getSubject());
        $email->html($message->getBody());

        $email->from(new Address($this->fromEmail, $this->fromName));

        return $email;
    }

    /**
     * Send all messages that are not sent yet.
     */
    public function sendAllMessages(): void
    {
        $this->acquireLock();

        $messages = $this->messageRepository->getAllMessageToSend();
        foreach ($messages as $message) {
            $this->sendMessage($message);
        }
    }

    /**
     * Acquire an exclusive lock.
     *
     * This is to ensure only one mailer can run at any given time. This is to prevent sending the same email twice.
     */
    private function acquireLock(): void
    {
        $lockFile = 'data/tmp/mailer.lock';
        touch($lockFile);
        $this->lock = fopen($lockFile, 'r+b');
        if ($this->lock === false) {
            throw new Exception('Could not read lock file. This is not normal and might be a permission issue');
        }

        if (!flock($this->lock, LOCK_EX | LOCK_NB)) {
            $message = LogRepository::MAILER_LOCKED;
            _log()->info($message);

            echo $message . PHP_EOL;
            echo 'If the problem persist and another mailing is not in progress, try deleting ' . $lockFile . PHP_EOL;

            // Not getting the lock is not considered as error to avoid being spammed
            exit();
        }
    }
}
