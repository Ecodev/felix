<?php

declare(strict_types=1);

namespace Ecodev\Felix;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'aliases' => [
                ],
                'invokables' => [
                    \Ecodev\Felix\DBAL\Logging\ForwardSQLLogger::class,
                    \Ecodev\Felix\Log\Formatter\Extras::class,
                ],
                'factories' => [
                    \Ecodev\Felix\Service\ImageResizer::class => \Ecodev\Felix\Service\ImageResizerFactory::class,
                    \Imagine\Image\ImagineInterface::class => \Ecodev\Felix\Service\ImagineFactory::class,
                    \Laminas\View\Renderer\RendererInterface::class => \Ecodev\Felix\Service\RendererFactory::class,
                    \Laminas\Mail\Transport\TransportInterface::class => \Ecodev\Felix\Service\TransportFactory::class,
                    \Ecodev\Felix\Log\Writer\Mail::class => \Ecodev\Felix\Log\Writer\MailFactory::class,
                    \Ecodev\Felix\Log\EventCompleter::class => \Ecodev\Felix\Log\EventCompleterFactory::class,
                    \Laminas\Log\LoggerInterface::class => \Ecodev\Felix\Log\LoggerFactory::class,
                    \Ecodev\Felix\Service\MessageRenderer::class => \Ecodev\Felix\Service\MessageRendererFactory::class,
                ],
            ],
        ];
    }
}
