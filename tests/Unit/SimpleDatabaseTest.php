<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SimpleDatabaseTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_can_connect_to_database()
    {
        $result = \DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
    }

    /** @test */
    public function it_can_check_database_name()
    {
        $result = \DB::select('SELECT DATABASE() as db_name');
        $this->assertEquals('nestogy_testing', $result[0]->db_name);
    }

    /** @test */
    public function it_can_list_tables()
    {
        $tables = \DB::select('SHOW TABLES');
        $this->assertNotEmpty($tables);
    }
}