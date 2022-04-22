<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\HelperPluginManager;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Renderer\RendererInterface;
use Laminas\View\Resolver\TemplatePathStack;
use Psr\Container\ContainerInterface;

final class RendererFactory implements FactoryInterface
{
    /**
     * Return a configured mailer.
     *
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): RendererInterface
    {
        $renderer = new PhpRenderer();

        /** @var HelperPluginManager $helperPluginManager */
        $helperPluginManager = $container->get(HelperPluginManager::class);
        $renderer->setHelperPluginManager($helperPluginManager);

        $resolver = new TemplatePathStack();
        $resolver->addPath('server/templates/app');
        $resolver->addPath('server/templates/emails');
        $renderer->setResolver($resolver);

        return $renderer;
    }
}
