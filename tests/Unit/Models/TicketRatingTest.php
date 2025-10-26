<?php

namespace Tests\Unit\Models;

use App\Domains\Ticket\Models\TicketRating;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class TicketRatingTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_ticket_rating_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = TicketRating::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TicketRating::class, $model);
    }

    public function test_ticket_rating_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = TicketRating::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_ticket_rating_has_fillable_attributes(): void
    {
        $model = new TicketRating();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
