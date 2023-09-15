<?php

declare(strict_types=1);

namespace EcodevTests\Felix\I18n;

use Ecodev\Felix\ConfigProvider;
use Ecodev\Felix\I18n\Translator;
use EcodevTests\Felix\Traits\TestWithContainer;
use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\I18n\Translator\Loader\PhpArray;
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase
{
    use TestWithContainer;

    public function testTrWithLaminasI18n(): void
    {
        $aggregator = new ConfigAggregator([
            ConfigProvider::class,
            \Laminas\I18n\ConfigProvider::class,
            new ArrayProvider([
                'translator' => [
                    'translation_files' => [
                        [
                            'type' => PhpArray::class,
                            'filename' => 'tests/I18n/fr.php',
                        ],
                    ],
                ],
                'dependencies' => [
                    'factories' => [
                        Translator::class => \Laminas\I18n\Translator\TranslatorServiceFactory::class,
                    ],
                ],
            ]),
        ]);

        $this->createContainer($aggregator);

        self::assertSame('translated value translated value', _tr('foo %param% param %param%', ['param' => 'value']));
    }

    public function testTrWithCustomTranslator(): void
    {
        $aggregator = new ConfigAggregator([
            new ArrayProvider([
                'dependencies' => [
                    'factories' => [
                        Translator::class => fn () => new class() implements Translator {
                            public function translate(string $message): string
                            {
                                return 'translated %param% translated %param%';
                            }
                        },
                    ],
                ],
            ]),
        ]);

        $this->createContainer($aggregator);

        self::assertSame('translated value translated value', _tr('foo %param% param %param%', ['param' => 'value']));
    }

    /**
     * @dataProvider providerTr
     */
    public function testTr(string $message, array $replacements, string $expected): void
    {
        $this->createDefaultFelixContainer();

        self::assertSame($expected, _tr($message, $replacements));
    }

    public static function providerTr(): array
    {
        return [
            [
                'foo',
                [],
                'foo',
            ],
            [
                'foo %param% param',
                [],
                'foo %param% param',
            ],
            [
                'foo %param% param %param%',
                ['param' => 'value'],
                'foo value param value',
            ],
            [
                '%not-recursive% %string% %int% %float% %null%',
                [
                    'not-recursive' => '%string%',
                    'string' => 'value',
                    'int' => 123,
                    'float' => 0.75,
                    'null' => null,
                ],
                '%string% value 123 0.75 ',
            ],
        ];
    }
}
