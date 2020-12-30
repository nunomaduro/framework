<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait RefreshDatabase
{
    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function refreshDatabase()
    {
        if ($this->usingInMemoryDatabase()) {
            return $this->refreshInMemoryDatabase();
        }

        if ($this->usingTemporaryDatabase()) {
            $this->switchToTemporaryDatabase();
        }

        $this->refreshTestDatabase();
    }

    /**
     * Determine if an in-memory database is being used.
     *
     * @return bool
     */
    protected function usingInMemoryDatabase()
    {
        $default = config('database.default');

        return config("database.connections.$default.database") === ':memory:';
    }

    /**
     * Determine if the current tests are been run in parallel.
     *
     * @return bool
     */
    protected function usingTemporaryDatabase()
    {
        return ! $this->usingInMemoryDatabase() && $this->getTemporaryDatabaseToken() !== false;
    }

    /**
     * Returns the temporary test token, if any.
     *
     * @return int|false
     */
    public function getTemporaryDatabaseToken()
    {
        return getenv('TEST_TOKEN');
    }

    /**
     * Switch to the temporary test database.
     *
     * @return void
     */
    protected function switchToTemporaryDatabase()
    {
        $default = config('database.default');

        config()->set(
            "database.connections.{$default}.database",
            RefreshDatabaseState::$temporaryDatabaseName,
        );
    }

    /**
     * Creates a temporary database, if needed.
     *
     * @beforeClass
     *
     * @return void
     */
    public static function setUpTemporaryDatabase()
    {
        tap(new static(), function ($testCase) {
            $testCase->refreshApplication();

            if (! $testCase->usingTemporaryDatabase()) {
                return;
            }

            $name = $testCase->getConnection()->getConfig('database');
            $name = "{$name}_test_{$testCase->getTemporaryDatabaseToken()}";

            Schema::createDatabaseIfNotExists(
                RefreshDatabaseState::$temporaryDatabaseName = $name
            );
        })->app->flush();
    }

    /**
     * Drop the temporary database, if any.
     *
     * @afterClass
     *
     * @return void
     */
    public static function tearDownTemporaryDatabase()
    {
        if (RefreshDatabaseState::$temporaryDatabaseName) {
            tap(new static(), function ($testCase) {
                $testCase->refreshApplication();

                Schema::dropDatabaseIfExists(
                    RefreshDatabaseState::$temporaryDatabaseName,
                );
            })->app->flush();
        }
    }

    /**
     * Refresh the in-memory database.
     *
     * @return void
     */
    protected function refreshInMemoryDatabase()
    {
        $this->artisan('migrate', $this->migrateUsing());

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * The parameters that should be used when running "migrate".
     *
     * @return array
     */
    protected function migrateUsing()
    {
        return [
            '--seed' => $this->shouldSeed(),
        ];
    }

    /**
     * Refresh a conventional test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * The parameters that should be used when running "migrate:fresh".
     *
     * @return array
     */
    protected function migrateFreshUsing()
    {
        return [
            '--drop-views' => $this->shouldDropViews(),
            '--drop-types' => $this->shouldDropTypes(),
            '--seed' => $this->shouldSeed(),
        ];
    }

    /**
     * Begin a database transaction on the testing database.
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        $database = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            $connection = $database->connection($name);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);
                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();
                $connection->rollback();
                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();
            }
        });
    }

    /**
     * The database connections that should have transactions.
     *
     * @return array
     */
    protected function connectionsToTransact()
    {
        return property_exists($this, 'connectionsToTransact')
                            ? $this->connectionsToTransact : [null];
    }

    /**
     * Determine if views should be dropped when refreshing the database.
     *
     * @return bool
     */
    protected function shouldDropViews()
    {
        return property_exists($this, 'dropViews') ? $this->dropViews : false;
    }

    /**
     * Determine if types should be dropped when refreshing the database.
     *
     * @return bool
     */
    protected function shouldDropTypes()
    {
        return property_exists($this, 'dropTypes') ? $this->dropTypes : false;
    }

    /**
     * Determine if the seed task should be run when refreshing the database.
     *
     * @return bool
     */
    protected function shouldSeed()
    {
        return property_exists($this, 'seed') ? $this->seed : false;
    }
}
