<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\MysqlGrammar;
use Illuminate\Database\Schema\MysqlBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMysqlBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCreateDatabaseIfNotExists()
    {
        $config = [
            'database' => 'my_database',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];

        $options = array_merge($config, [
            'database' => 'my_temporary_database',
        ]);

        $grammar = new MysqlGrammar();

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getConfig')->once()->andReturn($config);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'CREATE DATABASE IF NOT EXISTS my_temporary_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
        )->andReturn(true);

        $builder = new MysqlBuilder($connection);

        $builder->createDatabaseIfNotExists($options['database']);
    }

    public function testDropDatabaseIfExists()
    {
        $grammar = new MysqlGrammar();

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'DROP DATABASE IF EXISTS my_database_a;'
        )->andReturn(true);

        $builder = new MysqlBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }
}
