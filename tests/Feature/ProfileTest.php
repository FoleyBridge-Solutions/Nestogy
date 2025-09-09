<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_displays_for_authenticated_users()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/users/profile');
        
        $response->assertStatus(200);
        $response->assertSee('Profile Information');
        $response->assertSee('Update Password');
        $response->assertSee('Two Factor Authentication');
        $response->assertSee('Preferences');
    }

    public function test_profile_redirects_for_guests()
    {
        $response = $this->get('/users/profile');
        
        $response->assertRedirect('/login');
    }
}