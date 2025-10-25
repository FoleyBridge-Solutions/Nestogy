<?php

namespace Tests\Unit\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\Setting;
use App\Domains\Core\Models\Settings\HRSettings;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Services\OvertimeCalculationService;
use App\Domains\HR\Services\TimeClockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TimeClockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TimeClockService $service;
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
        
        $overtimeService = app(OvertimeCalculationService::class);
        $this->service = new TimeClockService($overtimeService);
    }

    public function test_clock_in_creates_time_entry(): void
    {
        $entry = $this->service->clockIn($this->user);

        $this->assertInstanceOf(EmployeeTimeEntry::class, $entry);
        $this->assertEquals($this->user->id, $entry->user_id);
        $this->assertEquals($this->company->id, $entry->company_id);
        $this->assertEquals(EmployeeTimeEntry::TYPE_CLOCK, $entry->entry_type);
        $this->assertEquals(EmployeeTimeEntry::STATUS_IN_PROGRESS, $entry->status);
        $this->assertNotNull($entry->clock_in);
        $this->assertNull($entry->clock_out);
    }

    public function test_clock_in_records_ip_address(): void
    {
        $entry = $this->service->clockIn($this->user, ['ip' => '192.168.1.100']);

        $this->assertEquals('192.168.1.100', $entry->clock_in_ip);
    }

    public function test_clock_in_records_gps_coordinates(): void
    {
        $entry = $this->service->clockIn($this->user, [
            'latitude' => 37.7749,
            'longitude' => -122.4194,
        ]);

        $this->assertEquals(37.7749, $entry->clock_in_latitude);
        $this->assertEquals(-122.4194, $entry->clock_in_longitude);
    }

    public function test_clock_in_records_metadata(): void
    {
        $entry = $this->service->clockIn($this->user, [
            'device' => 'iPhone 14',
            'method' => 'mobile',
        ]);

        $this->assertEquals('iPhone 14', $entry->metadata['device']);
        $this->assertEquals('mobile', $entry->metadata['clock_in_method']);
    }

    public function test_clock_in_rounds_time_when_configured(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setRoundToMinutes(15);
        $this->setting->save();

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:07:00'));

        $entry = $this->service->clockIn($this->user);

        $this->assertEquals('09:00:00', $entry->clock_in->format('H:i:s'));

        Carbon::setTestNow();
    }

    public function test_clock_in_throws_exception_when_already_clocked_in(): void
    {
        $this->service->clockIn($this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You already have an active time entry');

        $this->service->clockIn($this->user);
    }

    public function test_clock_out_updates_existing_entry(): void
    {
        $entry = $this->service->clockIn($this->user);
        
        Carbon::setTestNow(Carbon::parse($entry->clock_in)->addHours(8));

        $this->service->clockOut($entry);

        $this->assertNotNull($entry->clock_out);
        $this->assertGreaterThan(0, $entry->total_minutes);
        $this->assertContains($entry->status, [EmployeeTimeEntry::STATUS_APPROVED, EmployeeTimeEntry::STATUS_COMPLETED]);

        Carbon::setTestNow();
    }

    public function test_clock_out_records_ip_address(): void
    {
        $entry = $this->service->clockIn($this->user);
        
        $this->service->clockOut($entry, ['ip' => '192.168.1.101']);

        $this->assertEquals('192.168.1.101', $entry->clock_out_ip);
    }

    public function test_clock_out_records_gps_coordinates(): void
    {
        $entry = $this->service->clockIn($this->user);
        
        $this->service->clockOut($entry, [
            'latitude' => 37.8049,
            'longitude' => -122.2711,
        ]);

        $this->assertEquals(37.8049, $entry->clock_out_latitude);
        $this->assertEquals(-122.2711, $entry->clock_out_longitude);
    }

    public function test_clock_out_throws_exception_when_already_clocked_out(): void
    {
        $entry = $this->service->clockIn($this->user);
        $this->service->clockOut($entry);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Already clocked out');

        $this->service->clockOut($entry);
    }

    public function test_clock_out_calculates_time_breakdown(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));
        $entry = $this->service->clockIn($this->user);
        
        Carbon::setTestNow(Carbon::parse('2024-01-15 17:00:00'));
        $this->service->clockOut($entry);

        $this->assertEquals(480, $entry->total_minutes);
        $this->assertEquals(480, $entry->regular_minutes);

        Carbon::setTestNow();
    }

    public function test_clock_out_auto_approves_when_below_threshold(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setRequireApproval(true);
        $hrSettings->setApprovalThresholdHours(10);
        $this->setting->save();

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));
        $entry = $this->service->clockIn($this->user);
        
        Carbon::setTestNow(Carbon::parse('2024-01-15 17:00:00'));
        $this->service->clockOut($entry);

        $this->assertEquals(EmployeeTimeEntry::STATUS_APPROVED, $entry->status);
        $this->assertNotNull($entry->approved_at);

        Carbon::setTestNow();
    }

    public function test_clock_out_requires_approval_when_above_threshold(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setRequireApproval(true);
        $hrSettings->setApprovalThresholdHours(6);
        $this->setting->save();

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));
        $entry = $this->service->clockIn($this->user);
        
        Carbon::setTestNow(Carbon::parse('2024-01-15 17:00:00'));
        $this->service->clockOut($entry);

        $this->assertEquals(EmployeeTimeEntry::STATUS_COMPLETED, $entry->status);
        $this->assertNull($entry->approved_at);

        Carbon::setTestNow();
    }

    public function test_get_active_entry_returns_in_progress_entry(): void
    {
        $entry = $this->service->clockIn($this->user);

        $activeEntry = $this->service->getActiveEntry($this->user);

        $this->assertNotNull($activeEntry);
        $this->assertEquals($entry->id, $activeEntry->id);
    }

    public function test_get_active_entry_returns_null_when_no_active_entry(): void
    {
        $activeEntry = $this->service->getActiveEntry($this->user);

        $this->assertNull($activeEntry);
    }

    public function test_has_active_entry_returns_true_when_clocked_in(): void
    {
        $this->service->clockIn($this->user);

        $this->assertTrue($this->service->hasActiveEntry($this->user));
    }

    public function test_has_active_entry_returns_false_when_not_clocked_in(): void
    {
        $this->assertFalse($this->service->hasActiveEntry($this->user));
    }

    public function test_validate_clock_in_passes_with_valid_conditions(): void
    {
        $hrSettings = new HRSettings($this->setting);
        
        $result = $this->service->validateClockIn($this->user, $hrSettings);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function test_validate_clock_in_fails_when_already_clocked_in(): void
    {
        $this->service->clockIn($this->user);
        
        $hrSettings = new HRSettings($this->setting);
        $result = $this->service->validateClockIn($this->user, $hrSettings);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('already have an active time entry', $result->getErrors()[0]);
    }

    public function test_validate_clock_in_fails_when_gps_required_but_missing(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setRequireGPS(true);
        $this->setting->save();

        $result = $this->service->validateClockIn($this->user, $hrSettings);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('GPS location is required', $result->getErrors()[0]);
    }

    public function test_validate_clock_in_passes_when_gps_provided(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setRequireGPS(true);
        $this->setting->save();

        $result = $this->service->validateClockIn($this->user, $hrSettings, [
            'latitude' => 37.7749,
            'longitude' => -122.4194,
        ]);

        $this->assertTrue($result->isValid());
    }

    public function test_validate_clock_in_fails_when_ip_not_allowed(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setAllowedIPs(['192.168.1.0/24']);
        $this->setting->save();

        $result = $this->service->validateClockIn($this->user, $hrSettings, [
            'ip' => '10.0.0.1',
        ]);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('not allowed from this IP address', $result->getErrors()[0]);
    }

    public function test_validate_clock_in_passes_when_ip_in_allowed_range(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setAllowedIPs(['192.168.1.0/24']);
        $this->setting->save();

        $result = $this->service->validateClockIn($this->user, $hrSettings, [
            'ip' => '192.168.1.50',
        ]);

        $this->assertTrue($result->isValid());
    }

    public function test_validate_clock_in_passes_when_exact_ip_matches(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setAllowedIPs(['192.168.1.100']);
        $this->setting->save();

        $result = $this->service->validateClockIn($this->user, $hrSettings, [
            'ip' => '192.168.1.100',
        ]);

        $this->assertTrue($result->isValid());
    }

    public function test_auto_clock_out_stale_entries_clocks_out_old_entries(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setAutoClockOutHours(12);
        $this->setting->save();

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));
        $entry = $this->service->clockIn($this->user);
        
        Carbon::setTestNow(Carbon::parse('2024-01-16 09:00:00'));

        $results = $this->service->autoClockOutStaleEntries($this->company->id);

        $this->assertCount(1, $results);
        $this->assertEquals('success', $results[0]['status']);
        $this->assertEquals($entry->id, $results[0]['entry_id']);

        $entry->refresh();
        $this->assertNotNull($entry->clock_out);

        Carbon::setTestNow();
    }

    public function test_auto_clock_out_does_not_affect_recent_entries(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setAutoClockOutHours(12);
        $this->setting->save();

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));
        $entry = $this->service->clockIn($this->user);
        
        Carbon::setTestNow(Carbon::parse('2024-01-15 15:00:00'));

        $results = $this->service->autoClockOutStaleEntries($this->company->id);

        $this->assertEmpty($results);

        $entry->refresh();
        $this->assertNull($entry->clock_out);

        Carbon::setTestNow();
    }

    public function test_round_time_rounds_to_nearest_15_minutes(): void
    {
        $time = Carbon::parse('2024-01-15 09:07:00');
        $rounded = $this->service->clockIn($this->user);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('roundTime');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, $time, 15);

        $this->assertEquals('09:00:00', $result->format('H:i:s'));
    }

    public function test_round_time_rounds_up_when_closer(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('roundTime');
        $method->setAccessible(true);
        
        $time = Carbon::parse('2024-01-15 09:08:00');
        $result = $method->invoke($this->service, $time, 15);

        $this->assertEquals('09:15:00', $result->format('H:i:s'));
    }

    public function test_ip_in_range_matches_exact_ip(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('ipInRange');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, '192.168.1.100', '192.168.1.100');

        $this->assertTrue($result);
    }

    public function test_ip_in_range_matches_subnet(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('ipInRange');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, '192.168.1.50', '192.168.1.0/24');

        $this->assertTrue($result);
    }

    public function test_ip_in_range_rejects_outside_subnet(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('ipInRange');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, '192.168.2.50', '192.168.1.0/24');

        $this->assertFalse($result);
    }

    public function test_clock_in_logs_event(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Employee clocked in', \Mockery::type('array'));

        $this->service->clockIn($this->user);
    }

    public function test_clock_out_logs_event(): void
    {
        $entry = $this->service->clockIn($this->user);

        Log::shouldReceive('info')
            ->once()
            ->with('Employee clocked out', \Mockery::type('array'));

        $this->service->clockOut($entry);
    }
}
