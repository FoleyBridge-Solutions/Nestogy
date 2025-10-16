<?php

namespace Tests\Unit\Models;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Client\Models\Contact;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_contact_with_factory(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_contact_belongs_to_client(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertInstanceOf(Client::class, $contact->client);
        $this->assertEquals($client->id, $contact->client->id);
    }

    public function test_contact_has_name_and_email(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertEquals('John Doe', $contact->name);
        $this->assertEquals('john@example.com', $contact->email);
    }

    public function test_contact_has_phone_field(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'phone' => '555-1234',
        ]);

        $this->assertEquals('555-1234', $contact->phone);
    }

    public function test_contact_has_fillable_attributes(): void
    {
        $fillable = (new Contact)->getFillable();

        $expectedFillable = ['company_id', 'client_id', 'name', 'email', 'phone'];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_contact_has_timestamps(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertNotNull($contact->created_at);
        $this->assertNotNull($contact->updated_at);
    }

    public function test_contact_can_be_primary(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'primary' => true,
        ]);

        $this->assertTrue($contact->primary);
    }
}