<?php

namespace Tests\Unit\Models\Security;

use App\Domains\Core\Models\User;
use App\Domains\Security\Models\TrustedDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrustedDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_trusted_device(): void
    {
        $user = User::factory()->create();
        
        $device = TrustedDevice::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(TrustedDevice::class, $device);
        $this->assertDatabaseHas('trusted_devices', [
            'id' => $device->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $device = TrustedDevice::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $device->user);
        $this->assertEquals($user->id, $device->user->id);
    }

    public function test_casts_boolean_fields(): void
    {
        $device = TrustedDevice::factory()->create([
            'is_active' => true,
        ]);

        $this->assertIsBool($device->is_active);
        $this->assertTrue($device->is_active);
    }

    public function test_casts_datetime_fields(): void
    {
        $device = TrustedDevice::factory()->create([
            'last_used_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $device->last_used_at);
    }
}
