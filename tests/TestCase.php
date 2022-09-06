<?php

declare(strict_types=1);

namespace Dizions\Unclogged;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Dizions\Unclogged\Database\BasicConnectionParameters;
use Dizions\Unclogged\Database\ColumnSchema;
use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\Database\TableSchema;
use Dizions\Unclogged\Request\Request;
use Dizions\Unclogged\Setup\DefaultEnvironment;
use Dizions\Unclogged\Setup\Environment;

abstract class TestCase extends BaseTestCase
{
    protected function getDefaultEnvironment(): Environment
    {
        return (new DefaultEnvironment(''))->set('ENVIRONMENT_SEARCH_PATHS', [])->getEnvironment();
    }

    protected function createEmptyApplication(): Application
    {
        return new Application(new Environment([]), $this->createMock(Request::class));
    }

    protected function createEmptyDatabase(): Database
    {
        return new Database(new BasicConnectionParameters('sqlite', [':memory:']));
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

    protected function reformatSql(string $sql): string
    {
        return preg_replace(['/\(\s+/', '/\s+\)/', '/\s+/'], ['(', ')', ' '], $sql);
    }
}
