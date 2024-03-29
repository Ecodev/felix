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
                    \Ecodev\Felix\DBAL\Logging\ForwardSQLLogger::class,
                    \Ecodev\Felix\Log\Formatter\Extras::class,
                ],
                'factories' => [
                    \Ecodev\Felix\I18n\Translator::class => \Ecodev\Felix\I18n\NoTranslationFactory::class,
                    \Ecodev\Felix\Log\EventCompleter::class => \Ecodev\Felix\Log\EventCompleterFactory::class,
                    \Ecodev\Felix\Log\Writer\Mail::class => \Ecodev\Felix\Log\Writer\MailFactory::class,
                    \Ecodev\Felix\Middleware\SignedQueryMiddleware::class => \Ecodev\Felix\Middleware\SignedQueryMiddlewareFactory::class,
                    \Ecodev\Felix\Service\ImageResizer::class => \Ecodev\Felix\Service\ImageResizerFactory::class,
                    \Ecodev\Felix\Service\MessageRenderer::class => \Ecodev\Felix\Service\MessageRendererFactory::class,
                    \Imagine\Image\ImagineInterface::class => \Ecodev\Felix\Service\ImagineFactory::class,
                    \Laminas\Log\LoggerInterface::class => \Ecodev\Felix\Log\LoggerFactory::class,
                    \Laminas\Mail\Transport\TransportInterface::class => \Ecodev\Felix\Service\TransportFactory::class,
                    \Laminas\View\Renderer\RendererInterface::class => \Ecodev\Felix\Service\RendererFactory::class,
                ],
            ],
            'doctrine' => [
                'event_manager' => [
                    'orm_default' => [
                        'listeners' => [
                            [
                                'events' => [\Doctrine\ORM\Tools\ToolEvents::postGenerateSchema],
                                'listener' => \Ecodev\Felix\Service\EnumAutoMigrator::class,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
