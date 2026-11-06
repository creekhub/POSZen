<?php

namespace Tests\Feature;

use App\Livewire\Report;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessOverviewProfitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-05 12:00:00');

        Schema::create('Customer', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('Name');
        });
        Schema::create('Document', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('Number');
            $table->unsignedBigInteger('CustomerId')->nullable();
            $table->dateTime('DateCreated');
            $table->decimal('Total', 12, 2);
            $table->decimal('Discount', 12, 2)->default(0);
            $table->integer('DocumentTypeId')->default(2);
        });
        Schema::create('DocumentItem', function (Blueprint $table): void {
            $table->increments('Id');
            $table->unsignedBigInteger('DocumentId');
            $table->unsignedBigInteger('ProductId');
            $table->decimal('Quantity', 12, 2)->default(1);
            $table->decimal('TotalAfterDocumentDiscount', 12, 2)->nullable();
            $table->decimal('Total', 12, 2)->nullable();
            $table->decimal('PriceAfterDiscount', 12, 2)->nullable();
            $table->decimal('ProductCost', 12, 2)->nullable();
        });
        Schema::create('Product', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('Name');
            $table->string('Code')->nullable();
            $table->decimal('Cost', 12, 2)->nullable();
            $table->decimal('LastPurchasePrice', 12, 2)->nullable();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_business_overview_displays_profit_after_document_discount_and_cost_fallback(): void
    {
        DB::table('Customer')->insert(['Id' => 1, 'Name' => 'Overview customer']);
        DB::table('Product')->insert([
            ['Id' => 1, 'Name' => 'Captured cost', 'Code' => 'P-1', 'Cost' => 20, 'LastPurchasePrice' => 20],
            ['Id' => 2, 'Name' => 'Fallback cost', 'Code' => 'P-2', 'Cost' => 10, 'LastPurchasePrice' => 10],
        ]);
        DB::table('Document')->insert([
            'Id' => 1,
            'Number' => 'SALE-1',
            'CustomerId' => 1,
            'DateCreated' => '2026-11-05 10:00:00',
            'Total' => 90,
            'Discount' => 10,
            'DocumentTypeId' => 2,
        ]);
        DB::table('DocumentItem')->insert([
            ['DocumentId' => 1, 'ProductId' => 1, 'Quantity' => 1, 'TotalAfterDocumentDiscount' => 60, 'Total' => 60, 'PriceAfterDiscount' => 60, 'ProductCost' => 0],
            ['DocumentId' => 1, 'ProductId' => 2, 'Quantity' => 1, 'TotalAfterDocumentDiscount' => 40, 'Total' => 40, 'PriceAfterDiscount' => 40, 'ProductCost' => 0],
        ]);

        Livewire::test(Report::class)
            ->assertSeeInOrder(['Sales', '₱90.00', 'Profit', '₱60.00']);
    }
}
