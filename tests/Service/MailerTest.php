<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Service;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Ecodev\Felix\Model\Message;
use Ecodev\Felix\Model\User;
use Ecodev\Felix\Repository\MessageRepository;
use Ecodev\Felix\Service\Mailer;
use Laminas\Mail;
use Laminas\Mail\Address;
use Laminas\Mail\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;

final class MailerTest extends TestCase
{
    private function createMockMailer(): Mailer
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $transport = $this->createMockTransport();

        $messageRepository = new class() implements MessageRepository {
            public function getAllMessageToSend(): array
            {
                return [];
            }
        };

        $mailer = new Mailer(
            $entityManager,
            $messageRepository,
            $transport,
            '/user/bin/php',
            null,
            'noreply@example.com',
            'Epicerio'
        );

        return $mailer;
    }

    private function createMockTransport(): TransportInterface
    {
        return new class() implements TransportInterface {
            public function send(Mail\Message $message): void
            {
                // Purposefully place current cursor at the end of list
                foreach ($message->getFrom() as $a) {
                    $a->getEmail();
                }

                foreach ($message->getTo() as $a) {
                    $a->getEmail();
                }
            }
        };
    }

    public function testMockTransportHasCursorAtEndOfList(): void
    {
        $message = new Mail\Message();
        $message->setFrom('alice@exampl.com');
        $message->setTo('bob@exampl.com');

        // New message has current cursor on first element
        self::assertInstanceOf(Address::class, $message->getFrom()->current());
        self::assertInstanceOf(Address::class, $message->getTo()->current());

        $transport = $this->createMockTransport();
        $transport->send($message);

        // After transport, message has current cursor on end of list
        // @phpstan-ignore-next-line
        self::assertFalse($message->getFrom()->current());
        // @phpstan-ignore-next-line
        self::assertFalse($message->getTo()->current());
    }

    public function testSendMessage(): void
    {
        $mailer = $this->createMockMailer();
        $message = $this->createMockMessage();

        $this->expectOutputRegex('~email from noreply@example\.com sent to: john\.doe@example\.com~');
        $mailer->sendMessage($message);
        self::assertNotNull($message->getDateSent());
    }

    private function createMockMessage(): Message
    {
        return new class() implements Message {
            private ?Chronos $dateSent = null;

            public function getSubject(): string
            {
                return 'my subject';
            }

            public function getBody(): string
            {
                return 'my body';
            }

            public function setDateSent(?Chronos $dateSent): void
            {
                $this->dateSent = $dateSent;
            }

            public function getEmail(): string
            {
                return 'john.doe@example.com';
            }

            public function getRecipient(): ?User
            {
                return null;
            }

            public function getId(): ?int
            {
                return null;
            }

            public function setSubject(string $subject): void
            {
            }

            public function setBody(string $body): void
            {
            }

            public function getDateSent(): ?Chronos
            {
                return $this->dateSent;
            }

            public function setEmail(string $email): void
            {
            }

            public function getType(): string
            {
                return 'my_type';
            }
        };
    }
}
