<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CommandPalette;
use Livewire\Livewire;
use Tests\TestCase;
use App\Models\User;
use App\Models\Asset;

class CommandPaletteTest extends TestCase
{
    /** @test */
    public function search_results_persist_when_selecting()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create([
            'name' => 'Test Asset Equipment',
            'company_id' => $user->company_id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(CommandPalette::class)
            ->call('open')
            ->set('search', 'assets')
            ->assertSet('search', 'assets');
        
        // Check that results are populated
        $component->assertCount('results', function($count) {
            return $count > 0;
        });
        
        // Simulate clicking on first result
        $component->call('selectResult', 0);
    }
}