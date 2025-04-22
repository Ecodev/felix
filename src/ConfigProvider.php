<?php

declare(strict_types=1);

namespace Ecodev\Felix;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'signedQueries' => [
                'required' => true,
                'keys' => [],
                'allowedIps' => [],
            ],
            'dependencies' => [
                'invokables' => [
                    DBAL\Logging\Middleware::class,
                    Log\Formatter\Extras::class,
                ],
                'factories' => [
                    I18n\Translator::class => I18n\NoTranslationFactory::class,
                    Log\EventCompleter::class => Log\EventCompleterFactory::class,
                    Log\Writer\Mail::class => Log\Writer\MailFactory::class,
                    Middleware\SignedQueryMiddleware::class => Middleware\SignedQueryMiddlewareFactory::class,
                    Service\ImageResizer::class => Service\ImageResizerFactory::class,
                    Service\MessageRenderer::class => Service\MessageRendererFactory::class,
                    \Imagine\Image\ImagineInterface::class => Service\ImagineFactory::class,
                    \Laminas\Log\LoggerInterface::class => Log\LoggerFactory::class,
                    \Laminas\Mail\Transport\TransportInterface::class => Service\TransportFactory::class,
                    \Laminas\View\Renderer\RendererInterface::class => Service\RendererFactory::class,
                ],
            ],
        ];
    }
}
