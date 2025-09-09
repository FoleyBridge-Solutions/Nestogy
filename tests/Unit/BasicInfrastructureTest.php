<?php

namespace Tests\Unit;

use Tests\TestCase;

class BasicInfrastructureTest extends TestCase
{
    /** @test */
    public function it_can_create_application()
    {
        $this->assertNotNull($this->app);
    }

    /** @test */
    public function it_has_testing_environment()
    {
        $this->assertEquals('testing', config('app.env'));
    }

    /** @test */
    public function it_has_test_database_configured()
    {
        $this->assertEquals('nestogy_testing', config('database.connections.mysql.database'));
    }

    /** @test */
    public function it_has_bouncer_configured()
    {
        $this->assertTrue(class_exists('\Silber\Bouncer\BouncerFacade'));
    }
}