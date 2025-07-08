<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Service;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Ecodev\Felix\Model\Message;
use Ecodev\Felix\Model\User;
use Ecodev\Felix\Repository\MessageRepository;
use Ecodev\Felix\Service\Mailer;
use EcodevTests\Felix\Log\InMemoryTransport;
use PHPUnit\Framework\TestCase;

final class MailerTest extends TestCase
{
    private function createMockMailer(): Mailer
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $transport = new InMemoryTransport();

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
            'Epicerio',
        );

        return $mailer;
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

            public function setSubject(string $subject): void {}

            public function setBody(string $body): void {}

            public function getDateSent(): ?Chronos
            {
                return $this->dateSent;
            }

            public function setEmail(string $email): void {}

            public function getType(): string
            {
                return 'my_type';
            }
        };
    }
}
