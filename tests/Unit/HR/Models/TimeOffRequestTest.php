<?php

namespace Tests\Unit\HR\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\TimeOffRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeOffRequestTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_can_create_time_off_request(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => Carbon::parse('2025-02-01'),
            'end_date' => Carbon::parse('2025-02-05'),
            'is_full_day' => true,
            'total_hours' => 40,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertInstanceOf(TimeOffRequest::class, $request);
        $this->assertEquals($this->company->id, $request->company_id);
        $this->assertEquals($this->user->id, $request->user_id);
    }

    public function test_belongs_to_user(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today(),
            'end_date' => today()->addDays(2),
            'is_full_day' => true,
            'total_hours' => 24,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertInstanceOf(User::class, $request->user);
        $this->assertEquals($this->user->id, $request->user->id);
    }

    public function test_belongs_to_reviewed_by_user(): void
    {
        $reviewer = User::factory()->create(['company_id' => $this->company->id]);

        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today(),
            'end_date' => today()->addDays(2),
            'is_full_day' => true,
            'total_hours' => 24,
            'status' => TimeOffRequest::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $request->reviewedBy);
        $this->assertEquals($reviewer->id, $request->reviewedBy->id);
    }

    public function test_is_pending_status(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today(),
            'end_date' => today()->addDays(2),
            'is_full_day' => true,
            'total_hours' => 24,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertTrue($request->isPending());
        $this->assertFalse($request->isApproved());
        $this->assertFalse($request->isDenied());
    }

    public function test_is_approved_status(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today(),
            'end_date' => today()->addDays(2),
            'is_full_day' => true,
            'total_hours' => 24,
            'status' => TimeOffRequest::STATUS_APPROVED,
            'reviewed_by' => $this->user->id,
            'reviewed_at' => now(),
        ]);

        $this->assertTrue($request->isApproved());
        $this->assertFalse($request->isPending());
        $this->assertFalse($request->isDenied());
    }

    public function test_is_denied_status(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today(),
            'end_date' => today()->addDays(2),
            'is_full_day' => true,
            'total_hours' => 24,
            'status' => TimeOffRequest::STATUS_DENIED,
            'reviewed_by' => $this->user->id,
            'reviewed_at' => now(),
            'review_notes' => 'Insufficient PTO balance',
        ]);

        $this->assertTrue($request->isDenied());
        $this->assertFalse($request->isPending());
        $this->assertFalse($request->isApproved());
    }

    public function test_get_duration_days(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => Carbon::parse('2025-02-01'),
            'end_date' => Carbon::parse('2025-02-05'),
            'is_full_day' => true,
            'total_hours' => 40,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertEquals(5, $request->getDurationDays());
    }

    public function test_get_duration_days_single_day(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_SICK,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertEquals(1, $request->getDurationDays());
    }

    public function test_get_type_label_vacation(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertEquals('Vacation', $request->getTypeLabel());
    }

    public function test_get_type_label_sick(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_SICK,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertEquals('Sick Leave', $request->getTypeLabel());
    }

    public function test_get_type_label_personal(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_PERSONAL,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertEquals('Personal Day', $request->getTypeLabel());
    }

    public function test_get_type_label_unpaid(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_UNPAID,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertEquals('Unpaid Leave', $request->getTypeLabel());
    }

    public function test_get_type_label_holiday(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_HOLIDAY,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertEquals('Holiday', $request->getTypeLabel());
    }

    public function test_get_type_label_bereavement(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_BEREAVEMENT,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertEquals('Bereavement', $request->getTypeLabel());
    }

    public function test_scope_pending(): void
    {
        TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_SICK,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_APPROVED,
        ]);

        $this->assertEquals(1, TimeOffRequest::pending()->count());
    }

    public function test_scope_approved(): void
    {
        TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_APPROVED,
            'reviewed_by' => $this->user->id,
            'reviewed_at' => now(),
        ]);

        TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_SICK,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertEquals(1, TimeOffRequest::approved()->count());
    }

    public function test_scope_for_user(): void
    {
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);

        TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $otherUser->id,
            'type' => TimeOffRequest::TYPE_SICK,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertEquals(1, TimeOffRequest::forUser($this->user)->count());
    }

    public function test_scope_upcoming(): void
    {
        TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today()->addDays(5),
            'end_date' => today()->addDays(7),
            'is_full_day' => true,
            'total_hours' => 24,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_SICK,
            'start_date' => today()->subDays(2),
            'end_date' => today()->subDays(1),
            'is_full_day' => true,
            'total_hours' => 16,
            'status' => TimeOffRequest::STATUS_APPROVED,
        ]);

        $this->assertEquals(1, TimeOffRequest::upcoming()->count());
    }

    public function test_casts_dates_correctly(): void
    {
        $startDate = Carbon::parse('2025-02-01');
        $endDate = Carbon::parse('2025-02-05');

        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_full_day' => true,
            'total_hours' => 40,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertInstanceOf(Carbon::class, $request->start_date);
        $this->assertInstanceOf(Carbon::class, $request->end_date);
    }

    public function test_casts_boolean_correctly(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => true,
            'total_hours' => 8,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertIsBool($request->is_full_day);
        $this->assertTrue($request->is_full_day);
    }

    public function test_partial_day_request(): void
    {
        $request = TimeOffRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => TimeOffRequest::TYPE_PERSONAL,
            'start_date' => today(),
            'end_date' => today(),
            'is_full_day' => false,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'total_hours' => 4,
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->assertFalse($request->is_full_day);
        $this->assertEquals(4, $request->total_hours);
    }

    public function test_type_constants(): void
    {
        $this->assertEquals('vacation', TimeOffRequest::TYPE_VACATION);
        $this->assertEquals('sick', TimeOffRequest::TYPE_SICK);
        $this->assertEquals('personal', TimeOffRequest::TYPE_PERSONAL);
        $this->assertEquals('unpaid', TimeOffRequest::TYPE_UNPAID);
        $this->assertEquals('holiday', TimeOffRequest::TYPE_HOLIDAY);
        $this->assertEquals('bereavement', TimeOffRequest::TYPE_BEREAVEMENT);
    }

    public function test_status_constants(): void
    {
        $this->assertEquals('pending', TimeOffRequest::STATUS_PENDING);
        $this->assertEquals('approved', TimeOffRequest::STATUS_APPROVED);
        $this->assertEquals('denied', TimeOffRequest::STATUS_DENIED);
    }
}
