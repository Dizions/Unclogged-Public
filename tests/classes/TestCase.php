<?php

declare(strict_types=1);

namespace Dizions\Unclogged;

use Dizions\Unclogged\Database\BasicConnectionParameters;
use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\Database\Schema\{ColumnSchema, TableSchema};
use Dizions\Unclogged\Http\Request\Request;
use Dizions\Unclogged\Setup\{DefaultEnvironment, Environment};
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createEmptyApplication(): Application
    {
        return new Application(new Environment([]), $this->createMock(Request::class));
    }

    protected function createEmptyDatabase(): Database
    {
        return new Database(new BasicConnectionParameters('sqlite', [':memory:']));
    }

    protected function getDefaultEnvironment(): Environment
    {
        return DefaultEnvironment::fromGlobal([]);
    }

    /** @return array<int, string> */
    protected function getPeppers(): array
    {
        // Randomly generated for this test suite
        return [2 => 'yGC5V8mf8cdV8AzxGBSSRhXaPuRmmqtf', 3 => 'RcSpErElOxiYCwkpM5nEWZeb7RmNKS1J'];
    }

    protected function insertKtKeys(Database $db): void
    {
        $db->createTable(new TableSchema('keytable', [new ColumnSchema('kt_id'), new ColumnSchema('kt_key')]));
        $insert = $db->prepare('INSERT INTO keytable (kt_id, kt_key) VALUES (?, ?)');
        foreach ($this->getPeppers() as $id => $key) {
            $insert->execute([$id, $key]);
        }
    }

    protected function normaliseJson(string $in): string
    {
        return json_encode(
            json_decode($in, true, 512, JSON_THROW_ON_ERROR),
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    protected function reformatSql(string $sql): string
    {
        return preg_replace(['/\(\s+/', '/\s+\)/', '/\s+/'], ['(', ')', ' '], $sql);
    }

    /**
     * @param string $documentRoot
     * @param array<string, string> | string | null $ini Either a full path to a .ini file, or a
     *                                                   key-value array of PHP ini settings.
     * @return PhpServer
     */
    protected function startServer(string $documentRoot, array | string | null $iniFile = null): PhpServer
    {
        return PhpServer::start($documentRoot, $iniFile);
    }
}
