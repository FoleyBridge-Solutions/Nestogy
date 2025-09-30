<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;

class InvoiceItemTest extends ModelTestCase
{
    public function test_can_create_invoice_item_with_factory()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => 2,
            'price' => 50.00,
            'discount' => 0,
            'order' => 1,
        ]);

        $this->assertInstanceOf(InvoiceItem::class, $item);
        $this->assertEquals('Test Item', $item->name);
    }

    public function test_belongs_to_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'order' => 1,
        ]);

        $this->assertInstanceOf(Invoice::class, $item->invoice);
        $this->assertEquals($invoice->id, $item->invoice->id);
    }

    public function test_belongs_to_product()
    {
        $product = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => 1,
            'price' => $product->base_price,
            'discount' => 0,
            'order' => 1,
        ]);

        $this->assertInstanceOf(Product::class, $item->product);
        $this->assertEquals($product->id, $item->product->id);
    }

    public function test_has_fillable_attributes()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'description' => 'Test Description',
            'quantity' => 2,
            'price' => 50.00,
            'discount' => 10.00,
            'order' => 1,
        ]);

        $this->assertEquals('Test Item', $item->name);
        $this->assertEquals('Test Description', $item->description);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals(50.00, $item->price);
        $this->assertEquals(10.00, $item->discount);
    }

    public function test_calculates_subtotal_automatically()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => 2,
            'price' => 50.00,
            'discount' => 0,
            'order' => 1,
        ]);

        $this->assertEquals(100.00, $item->subtotal);
    }

    public function test_calculates_total_with_discount()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => 2,
            'price' => 50.00,
            'discount' => 10.00,
            'order' => 1,
        ]);

        $this->assertEquals(100.00, $item->subtotal);
        $this->assertEquals(90.00, $item->total);
    }

    public function test_has_discount_method()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $itemWithDiscount = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Item 1',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 10.00,
            'order' => 1,
        ]);

        $itemNoDiscount = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Item 2',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'order' => 2,
        ]);

        $this->assertTrue($itemWithDiscount->hasDiscount());
        $this->assertFalse($itemNoDiscount->hasDiscount());
    }

    public function test_calculates_discount_percentage()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 25.00,
            'order' => 1,
        ]);

        $this->assertEquals(25.00, $item->getDiscountPercentage());
    }

    public function test_formatted_quantity()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => 2.5,
            'price' => 100.00,
            'discount' => 0,
            'order' => 1,
        ]);

        $this->assertEquals('2.50', $item->getFormattedQuantity());
    }

    public function test_formatted_price()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => 1,
            'price' => 123.45,
            'discount' => 0,
            'order' => 1,
        ]);

        $this->assertEquals('$123.45', $item->getFormattedPrice());
    }

    public function test_formatted_total()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => 2,
            'price' => 50.00,
            'discount' => 0,
            'order' => 1,
        ]);

        $this->assertEquals('$100.00', $item->getFormattedTotal());
    }

    public function test_create_from_product_static_method()
    {
        $product = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'name' => 'Test Product',
            'base_price' => 99.99,
        ]);

        $itemData = InvoiceItem::createFromProduct($product, 3);

        $this->assertEquals('Test Product', $itemData['name']);
        $this->assertEquals(3, $itemData['quantity']);
        $this->assertEquals(99.99, $itemData['price']);
        $this->assertEquals($product->id, $itemData['product_id']);
    }

    public function test_scope_for_invoice()
    {
        $invoice1 = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $invoice2 = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice1->id,
            'name' => 'Item 1',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'order' => 1,
        ]);

        InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice2->id,
            'name' => 'Item 2',
            'quantity' => 1,
            'price' => 200.00,
            'discount' => 0,
            'order' => 1,
        ]);

        $items = InvoiceItem::forInvoice($invoice1->id)->get();

        $this->assertEquals(1, $items->count());
        $this->assertEquals('Item 1', $items->first()->name);
    }

    public function test_scope_ordered()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Third Item',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'order' => 3,
        ]);

        InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'First Item',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'order' => 1,
        ]);

        InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Second Item',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'order' => 2,
        ]);

        $items = InvoiceItem::forInvoice($invoice->id)->ordered()->get();

        $this->assertEquals('First Item', $items->first()->name);
        $this->assertEquals('Third Item', $items->last()->name);
    }

    public function test_soft_deletes_with_archived_at()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'order' => 1,
        ]);

        $item->delete();

        $this->assertSoftDeleted('invoice_items', ['id' => $item->id]);
        $this->assertNotNull($item->fresh()->archived_at);
    }

    public function test_casts_attributes_correctly()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $item = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Test Item',
            'quantity' => '2.50',
            'price' => '50.00',
            'discount' => '10.00',
            'order' => '1',
        ]);

        $this->assertIsString($item->quantity);
        $this->assertIsString($item->price);
        $this->assertIsInt($item->order);
    }

    public function test_is_voip_service_method()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        $regularItem = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Regular Item',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'order' => 1,
        ]);

        $voipItem = InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'VoIP Service',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'service_type' => 'local_voip',
            'order' => 2,
        ]);

        $this->assertFalse($regularItem->isVoIPService());
        $this->assertTrue($voipItem->isVoIPService());
    }

    public function test_scope_voip_services()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
        ]);

        InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'Regular Item',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'order' => 1,
        ]);

        InvoiceItem::create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'name' => 'VoIP Service',
            'quantity' => 1,
            'price' => 100.00,
            'discount' => 0,
            'service_type' => 'local_voip',
            'order' => 2,
        ]);

        $voipItems = InvoiceItem::voipServices()->get();

        $this->assertEquals(1, $voipItems->count());
        $this->assertEquals('VoIP Service', $voipItems->first()->name);
    }

    public function test_validation_rules_exist()
    {
        $rules = InvoiceItem::getValidationRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('quantity', $rules);
        $this->assertArrayHasKey('price', $rules);
    }
}