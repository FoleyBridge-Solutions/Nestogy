<?php

namespace Tests\Feature\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\Setting;
use App\Domains\Core\Models\Settings\HRSettings;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\Shift;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeClockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Setting $setting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->setting = Setting::firstOrCreate(
            ['company_id' => $this->company->id],
            Setting::factory()->make()->toArray()
        );
    }

    public function test_clock_in_creates_entry(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('hr.time-clock.clock-in'));

        $response->assertRedirect(route('hr.time-clock.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employee_time_entries', [
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_clock_in_with_gps_coordinates(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('hr.time-clock.clock-in'), [
                'latitude' => 37.7749,
                'longitude' => -122.4194,
            ]);

        $response->assertRedirect(route('hr.time-clock.index'));

        $this->assertDatabaseHas('employee_time_entries', [
            'user_id' => $this->user->id,
            'clock_in_latitude' => 37.7749,
            'clock_in_longitude' => -122.4194,
        ]);
    }

    public function test_clock_in_with_shift(): void
    {
        $shift = Shift::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->user)
            ->post(route('hr.time-clock.clock-in'), [
                'shift_id' => $shift->id,
            ]);

        $response->assertRedirect(route('hr.time-clock.index'));

        $this->assertDatabaseHas('employee_time_entries', [
            'user_id' => $this->user->id,
            'shift_id' => $shift->id,
        ]);
    }

    public function test_clock_in_returns_json_when_requested(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('hr.time-clock.clock-in'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'entry' => ['id', 'clock_in'],
        ]);
    }

    public function test_clock_in_fails_when_already_clocked_in(): void
    {
        EmployeeTimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('hr.time-clock.clock-in'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_clock_in_validates_coordinates(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('hr.time-clock.clock-in'), [
                'latitude' => 'invalid',
                'longitude' => 'invalid',
            ]);

        $response->assertSessionHasErrors(['latitude', 'longitude']);
    }

    public function test_clock_out_updates_entry(): void
    {
        $entry = EmployeeTimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('hr.time-clock.clock-out'));

        $response->assertRedirect(route('hr.time-clock.index'));
        $response->assertSessionHas('success');

        $entry->refresh();
        $this->assertNotNull($entry->clock_out);
        $this->assertGreaterThan(0, $entry->total_minutes);
    }

    public function test_clock_out_with_notes(): void
    {
        $entry = EmployeeTimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('hr.time-clock.clock-out'), [
                'notes' => 'Worked on project X',
            ]);

        $response->assertRedirect(route('hr.time-clock.index'));

        $entry->refresh();
        $this->assertEquals('Worked on project X', $entry->notes);
    }

    public function test_clock_out_with_break_minutes(): void
    {
        $entry = EmployeeTimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('hr.time-clock.clock-out'), [
                'break_minutes' => 30,
            ]);

        $response->assertRedirect(route('hr.time-clock.index'));

        $entry->refresh();
        $this->assertNotNull($entry->clock_out);
    }

    public function test_clock_out_returns_json_when_requested(): void
    {
        $entry = EmployeeTimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('hr.time-clock.clock-out'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'entry',
            'hours',
        ]);
    }

    public function test_clock_out_fails_without_active_entry(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('hr.time-clock.clock-out'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_clock_out_validates_break_minutes(): void
    {
        $entry = EmployeeTimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('hr.time-clock.clock-out'), [
                'break_minutes' => -10,
            ]);

        $response->assertSessionHasErrors(['break_minutes']);
    }

    public function test_status_returns_active_entry(): void
    {
        $entry = EmployeeTimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('hr.time-clock.status'));

        $response->assertOk();
        $response->assertJson([
            'is_clocked_in' => true,
            'active_entry' => [
                'id' => $entry->id,
            ],
        ]);
    }

    public function test_status_returns_null_when_not_clocked_in(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('hr.time-clock.status'));

        $response->assertOk();
        $response->assertJson([
            'is_clocked_in' => false,
            'active_entry' => null,
        ]);
    }

    public function test_history_returns_user_entries(): void
    {
        EmployeeTimeEntry::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'clock_in' => Carbon::now()->subDays(1),
            'clock_out' => Carbon::now()->subDays(1)->addHours(8),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hr.time-clock.history'));

        $response->assertOk();
        $response->assertViewIs('hr.time-clock.history');
        $response->assertViewHas('entries');
    }

    public function test_history_filters_by_date_range(): void
    {
        EmployeeTimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
        ]);

        EmployeeTimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'clock_in' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-15 17:00:00'),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hr.time-clock.history', [
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-10',
            ]));

        $response->assertOk();
    }

    public function test_history_returns_json_when_requested(): void
    {
        EmployeeTimeEntry::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'clock_in' => Carbon::now()->subDays(1),
            'clock_out' => Carbon::now()->subDays(1)->addHours(8),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('hr.time-clock.history'));

        $response->assertOk();
        $response->assertJsonStructure(['entries']);
    }

    public function test_history_does_not_show_other_users_entries(): void
    {
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        
        EmployeeTimeEntry::factory()->create([
            'user_id' => $otherUser->id,
            'company_id' => $this->company->id,
            'clock_in' => Carbon::now()->subDays(1),
            'clock_out' => Carbon::now()->subDays(1)->addHours(8),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('hr.time-clock.history'));

        $response->assertOk();
        $entries = $response->json('entries.data');
        
        foreach ($entries as $entry) {
            $this->assertEquals($this->user->id, $entry['user_id']);
        }
    }

    public function test_guest_cannot_access_time_clock(): void
    {
        $response = $this->get(route('hr.time-clock.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_clock_in(): void
    {
        $response = $this->post(route('hr.time-clock.clock-in'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_clock_out(): void
    {
        $response = $this->post(route('hr.time-clock.clock-out'));
        $response->assertRedirect(route('login'));
    }
}
