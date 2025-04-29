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
            'logSql' => false,
            'doctrine' => [
                'configuration' => [
                    'orm_default' => [
                        // Log all SQL queries from Doctrine (to logs/all.log)
                        'middlewares' => [DBAL\Logging\Middleware::class],
                    ],
                ],
            ],
            'dependencies' => [
                'invokables' => [
                ],
                'factories' => [
                    DBAL\Logging\Middleware::class => DBAL\Logging\MiddlewareFactory::class,
                    Log\Handler\DbHandler::class => Log\Handler\DbHandlerFactory::class,
                    I18n\Translator::class => I18n\NoTranslationFactory::class,
                    Log\RecordCompleter::class => Log\RecordCompleterFactory::class,
                    Log\Handler\MailerHandler::class => Log\Handler\MailerHandlerFactory::class,
                    Middleware\SignedQueryMiddleware::class => Middleware\SignedQueryMiddlewareFactory::class,
                    Service\ImageResizer::class => Service\ImageResizerFactory::class,
                    Service\MessageRenderer::class => Service\MessageRendererFactory::class,
                    \Imagine\Image\ImagineInterface::class => Service\ImagineFactory::class,
                    \Psr\Log\LoggerInterface::class => Log\LoggerFactory::class,
                    \Symfony\Component\Mailer\Transport\TransportInterface::class => Service\TransportFactory::class,
                    \Laminas\View\Renderer\RendererInterface::class => Service\RendererFactory::class,
                ],
            ],
        ];
    }
}
