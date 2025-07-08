<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Service;

use Ecodev\Felix\Service\MessageRenderer;
use EcodevTests\Felix\Blog\Model\User;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\RendererInterface;
use PHPUnit\Framework\TestCase;

final class MessageRendererTest extends TestCase
{
    public function testRender(): void
    {
        $user = new User();
        $email = 'foo@example.com';
        $subject = 'my subject';
        $type = 'my_type';
        $layoutParams = ['fooLayout' => 'barLayout'];
        $mailParams = ['fooMail' => 'barMail'];

        $viewRenderer = $this->createMock(RendererInterface::class);
        $matcher = self::exactly(2);
        $viewRenderer->expects($matcher)
            ->method('render')
            ->willReturnCallback(function (ViewModel $viewModel) use ($matcher, $user) {
                $callback = match ($matcher->getInvocationCount()) {
                    1 => (function () use ($viewModel, $user) {
                        $variables = [
                            'fooMail' => 'barMail',
                            'email' => 'foo@example.com',
                            'user' => $user,
                            'serverUrl' => 'https://example.com',
                        ];

                        self::assertSame('my-type', $viewModel->getTemplate());
                        self::assertSame($variables, $viewModel->getVariables());

                        return 'mocked-rendered-view';
                    }),
                    2 => (function () use ($viewModel, $user) {
                        $variables = [
                            'fooLayout' => 'barLayout',
                            'content' => 'mocked-rendered-view',
                            'subject' => 'my subject',
                            'user' => $user,
                            'serverUrl' => 'https://example.com',
                            'hostname' => 'example.com',
                        ];

                        self::assertSame('layout', $viewModel->getTemplate());
                        self::assertSame($variables, $viewModel->getVariables());

                        return 'mocked-rendered-layout';
                    }),
                    default => fn () => $this->fail(),
                };

                return $callback();
            });

        $messageRenderer = new MessageRenderer($viewRenderer, 'example.com');

        $messageRenderer->render($user, $email, $subject, $type, $mailParams, $layoutParams);
    }
}
