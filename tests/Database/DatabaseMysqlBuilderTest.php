<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\MysqlProcessor;
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

    public function testCreateDatabaseIfNotExistsName()
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
        $connection->shouldReceive('getConfig')->andReturn($config);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->with(
            'CREATE DATABASE IF NOT EXISTS my_temporary_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
        )->andReturn(true);

        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = new MysqlBuilder($connection);

        $builder->createDatabaseIfNotExists($options['database']);
    }

    public function testCreateDatabaseIfNotExistsOptions()
    {
        $config = [
            'database' => 'my_database',
            'charset' => 'utf8_foo',
            'collation' => 'utf8mb4_bar',
        ];

        $options = array_merge($config, [
            'database' => 'my_temporary_database',
        ]);

        $grammar = new MysqlGrammar();

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getConfig')->andReturn($config);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->with(
            'CREATE DATABASE IF NOT EXISTS my_temporary_database CHARACTER SET utf8_foo COLLATE utf8mb4_bar;'
        )->andReturn(true);

        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = new MysqlBuilder($connection);

        $builder->createDatabaseIfNotExists($options['database']);
    }
}
