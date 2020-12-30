<?php

namespace Illuminate\Testing;

use Illuminate\Contracts\Foundation\Application;

class Testing
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The token resolver callback.
     *
     * @var \Closure|null
     */
    protected static $tokenResolver;

    /**
     * Create a new Testing instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Adds an unique test token to the given string, if needed.
     *
     * @return string
     */
    public function addTokenIfNeeded($string)
    {
        if (! $this->inParallel()) {
            return $string;
        }

        return "{$string}_test_{$this->token()}";
    }

    /**
     * Indicates if the current tests are been run in Parallel.
     *
     * @return bool
     */
    public function inParallel()
    {
        return $this->app->runningUnitTests() && $this->token();
    }

    /**
     * Gets an unique test token.
     *
     * @return int|false
     */
    protected function token()
    {
        return static::$tokenResolver
            ? call_user_func(static::$tokenResolver)
            : getenv('TEST_TOKEN');
    }

    /**
     * Set with token resolver callback.
     *
     * @param  \Closure|null  $resolver
     * @return void
     */
    public static function tokenResolver($resolver)
    {
        static::$tokenResolver = $resolver;
    }
}
