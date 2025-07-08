<?php

declare(strict_types=1);

namespace Ecodev\Felix\Testing\Api;

use Ecodev\Felix\Api\Server;
use Ecodev\Felix\Testing\Traits\TestWithTransaction;
use Exception;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\Session;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Throwable;

abstract class AbstractServer extends TestCase
{
    use TestWithTransaction;

    /**
     * Should get user and call User::setCurrent().
     */
    abstract protected function setCurrentUser(?string $user): void;

    abstract protected function createSchema(): Schema;

    protected function createServer(): Server
    {
        return new Server($this->createSchema(), true);
    }

    public function testSchemaIsValid(): void
    {
        $schema = $this->createSchema();
        $schema->assertValid();

        self::assertTrue(true, 'schema passes validation');
    }

    /**
     * @dataProvider providerQuery
     */
    public function testQuery(?string $user, ServerRequest $request, array $expected, ?callable $dataPreparator = null, ?callable $additionalAsserts = null): void
    {
        $this->setCurrentUser($user);

        if ($dataPreparator) {
            $dataPreparator(_em()->getConnection());
        }

        // Configure server
        $server = $this->createServer();

        // Execute query
        $result = $server->execute($request);

        $actual = $this->resultToArray($result);
        $actualWithoutTrace = $this->removeTrace($actual);

        try {
            self::assertEquals($expected, $actualWithoutTrace);
        } catch (Throwable $e) {
            // If assertion fails, print the version of the result with trace for easier debugging
            ve($actual);

            throw $e;
        }

        if ($additionalAsserts) {
            $additionalAsserts(_em()->getConnection());
        }
    }

    public static function providerQuery(): iterable
    {
        $data = [];
        $files = glob('tests/data/query/*.php');
        if ($files === false) {
            throw new Exception('Could not find any queries to test server');
        }

        foreach ($files as $file) {
            $name = str_replace('-', ' ', basename($file, '.php'));
            $user = preg_replace('/\d/', '', explode(' ', $name)[0]);
            if ($user === 'anonymous') {
                $user = null;
            }

            $args = require $file;

            // Convert arg into request
            $request = new ServerRequest();
            $args[0] = $request
                ->withParsedBody($args[0])
                ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, new Session([]))
                ->withMethod('POST')
                ->withHeader('content-type', ['application/json']);

            array_unshift($args, $user);
            $data[$name] = $args;
        }

        return $data;
    }

    /**
     * @param ExecutionResult|ExecutionResult[] $result
     */
    private function resultToArray(array|ExecutionResult $result): array
    {
        if (is_array($result)) {
            foreach ($result as &$one) {
                $one = $one->toArray();
            }
        } else {
            $result = $result->toArray();
        }

        return $result;
    }

    private function removeTrace(array $result): array
    {
        if (array_key_exists('errors', $result)) {
            $result = $this->removeTraceOneResult($result);
        } else {
            foreach ($result as &$r) {
                $r = $this->removeTraceOneResult($r);
            }
        }

        return $result;
    }

    private function removeTraceOneResult(array $result): array
    {
        if (array_key_exists('errors', $result)) {
            foreach ($result['errors'] as &$error) {
                unset($error['extensions']['file'], $error['extensions']['line'], $error['extensions']['trace']);

                if (array_key_exists('extensions', $error) && !$error['extensions']) {
                    unset($error['extensions']);
                }
            }
        }

        return $result;
    }
}
