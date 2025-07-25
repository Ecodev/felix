<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Ecodev\Felix\Model\User;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\RendererInterface;

/**
 * Service to render message to HTML.
 */
final class MessageRenderer
{
    public function __construct(
        private readonly RendererInterface $viewRenderer,
        private string $hostname,
    ) {}

    /**
     * Render a message by templating.
     */
    public function render(?User $user, string $email, string $subject, string $type, array $mailParams, array $layoutParams = [], ?string $hostname = null): string
    {
        // Override hostname if given
        $hostname ??= $this->hostname;

        // First render the view
        $serverUrl = 'https://' . $hostname;
        $model = new ViewModel($mailParams);
        $model->setTemplate(str_replace('_', '-', $type));
        $model->setVariable('email', $email);
        $model->setVariable('user', $user);
        $model->setVariable('serverUrl', $serverUrl);
        $partialContent = $this->viewRenderer->render($model);

        // Then inject it into layout
        $layoutModel = new ViewModel($layoutParams);
        $layoutModel->setTemplate('layout');
        $layoutModel->setVariable($model->captureTo(), $partialContent);
        $layoutModel->setVariable('subject', $subject);
        $layoutModel->setVariable('user', $user);
        $layoutModel->setVariable('serverUrl', $serverUrl);
        $layoutModel->setVariable('hostname', $hostname);
        $content = $this->viewRenderer->render($layoutModel);

        return $content;
    }
}
