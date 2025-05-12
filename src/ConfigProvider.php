<?php

declare(strict_types=1);

namespace Ecodev\Felix;

use Ecodev\Felix\DBAL\EventListener\HideMigrationStorage;
use Ecodev\Felix\DBAL\EventListener\HideMigrationStorageFactory;

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
                        'schema_assets_filter' => HideMigrationStorage::class,
                    ],
                ],
            ],
            'dependencies' => [
                'aliases' => [
                    \Doctrine\ORM\EntityManager::class => 'doctrine.entity_manager.orm_default',
                ],
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
                    \Doctrine\Migrations\DependencyFactory::class => \Roave\PsrContainerDoctrine\Migrations\DependencyFactoryFactory::class,
                    \Symfony\Component\Console\Application::class => Console\ApplicationFactory::class,
                    \Doctrine\Migrations\Configuration\Migration\ConfigurationLoader::class => \Roave\PsrContainerDoctrine\Migrations\ConfigurationLoaderFactory::class,
                    HideMigrationStorage::class => HideMigrationStorageFactory::class,
                ],
            ],
        ];
    }
}
