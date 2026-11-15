<?php

namespace Tests\Feature;

use App\Livewire\ReportProductSales;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ReportProductSalesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-14 12:00:00');

        Schema::create('ProductGroup', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('Name');
        });
        Schema::create('Product', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('Name');
            $table->unsignedInteger('ProductGroupId')->nullable();
            $table->decimal('Cost', 12, 2)->nullable();
            $table->decimal('LastPurchasePrice', 12, 2)->nullable();
        });
        Schema::create('Document', function (Blueprint $table): void {
            $table->increments('Id');
            $table->integer('DocumentTypeId');
            $table->dateTime('Date');
            $table->decimal('Discount', 12, 2)->default(0);
        });
        Schema::create('DocumentItem', function (Blueprint $table): void {
            $table->increments('Id');
            $table->unsignedInteger('DocumentId');
            $table->unsignedInteger('ProductId');
            $table->decimal('Quantity', 12, 2)->default(1);
            $table->decimal('TotalAfterDocumentDiscount', 12, 2)->nullable();
            $table->decimal('Total', 12, 2)->nullable();
            $table->decimal('PriceAfterDiscount', 12, 2)->nullable();
            $table->decimal('ProductCost', 12, 2)->nullable();
            $table->decimal('Discount', 12, 2)->default(0);
        });

        DB::table('ProductGroup')->insert([
            ['Id' => 1, 'Name' => 'Food'],
            ['Id' => 2, 'Name' => 'Supplies'],
        ]);
        DB::table('Product')->insert([
            ['Id' => 1, 'Name' => 'Coffee', 'ProductGroupId' => 1, 'Cost' => 20, 'LastPurchasePrice' => 20],
            ['Id' => 2, 'Name' => 'Cup', 'ProductGroupId' => 2, 'Cost' => 10, 'LastPurchasePrice' => 10],
        ]);
        DB::table('Document')->insert([
            ['Id' => 1, 'DocumentTypeId' => 2, 'Date' => '2026-11-13 10:00:00', 'Discount' => 10],
        ]);
        DB::table('DocumentItem')->insert([
            ['DocumentId' => 1, 'ProductId' => 1, 'Quantity' => 1, 'Total' => 60, 'PriceAfterDiscount' => 60, 'ProductCost' => 20, 'Discount' => 0],
            ['DocumentId' => 1, 'ProductId' => 2, 'Quantity' => 1, 'Total' => 40, 'PriceAfterDiscount' => 40, 'ProductCost' => 10, 'Discount' => 0],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_all_category_report_includes_all_categories_and_allocates_order_discount(): void
    {
        $component = Livewire::test(ReportProductSales::class)
            ->set('productCategoryIds', ['all'])
            ->set('productStartDate', '2026-11-13')
            ->set('productEndDate', '2026-11-13')
            ->call('searchProducts')
            ->assertHasNoErrors();

        $report = $component->get('productReport');

        $this->assertSame(['Food', 'Supplies'], array_column($report['products'], 'product_name'));
        $this->assertEquals(90, $report['totalAmount']);
        $this->assertEquals(60, $report['totalProfit']);
        $this->assertEquals(10, $report['totalDiscount']);
        $this->assertEquals(2, $report['totalQuantity']);
    }
}
