<?php

namespace Tests\Feature\Livewire\Contracts;

use App\Livewire\Contracts\ContractLanguageEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class ContractLanguageEditorTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(ContractLanguageEditor::class)
            ->assertStatus(200);
    }
}
