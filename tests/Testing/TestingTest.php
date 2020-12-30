<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Testing\Testing;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TestingTest extends TestCase
{
    public function testInParallel()
    {
        $app = m::mock(Application::class);

        $app->shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(false);

        $this->assertFalse((new Testing($app))->inParallel());

        $app->shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(true);

        Testing::tokenResolver(function () {
            return 1;
        });

        $this->assertTrue((new Testing($app))->inParallel());
    }

    public function testAddTokenIfNeeded()
    {
        $app = m::mock(Application::class);

        $app->shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(false);

        $this->assertSame(
            'my_local_storage',
            (new Testing($app))->addTokenIfNeeded('my_local_storage')
        );

        $app->shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(true);

        Testing::tokenResolver(function () {
            return 1;
        });

        $this->assertSame(
            'my_local_storage_test_1',
            (new Testing($app))->addTokenIfNeeded('my_local_storage')
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        m::close();
        Testing::tokenResolver(null);
    }
}
