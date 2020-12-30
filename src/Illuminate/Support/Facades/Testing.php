<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string addTokenTo(string $string)
 * @method static bool inParallel()
 * @method static int|false token()
 *
 * @see \Illuminate\Testing\Testing
 */
class Testing extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Illuminate\Testing\Testing::class;
    }
}
