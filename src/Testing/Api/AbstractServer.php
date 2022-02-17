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

abstract class AbstractServer extends TestCase
{
    use TestWithTransaction;

    /**
     * Should get user and call User::setCurrent().
     */
    abstract protected function setCurrentUser(?string $user): void;

    abstract protected function createSchema(): Schema;

    protected function createServer(bool $debug): Server
    {
        return new Server($this->createSchema(), $debug);
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

        // Use this flag to easily debug API test issues
        /** @var bool $debug */
        $debug = false;

        // Configure server
        $server = $this->createServer($debug);

        // Execute query
        $result = $server->execute($request);

        $actual = $this->resultToArray($result, $debug);

        if ($debug) {
            ve($actual);
            unset($actual['errors'][0]['trace']);
        }

        self::assertEquals($expected, $actual);

        if ($additionalAsserts) {
            $additionalAsserts(_em()->getConnection());
        }
    }

    public function providerQuery(): array
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
    private function resultToArray(array|ExecutionResult $result, bool $debug): array
    {
        if (is_array($result)) {
            foreach ($result as &$one) {
                $one = $this->oneResultToArray($one, $debug);
            }
        } else {
            $result = $this->oneResultToArray($result, $debug);
        }

        return $result;
    }

    private function oneResultToArray(ExecutionResult $result, bool $debug): array
    {
        $result = $result->toArray();
        if ($debug) {
            ve($result);
            // @phpstan-ignore-next-line
            unset($result['errors'][0]['trace']);
        }

        return $result;
    }
}
