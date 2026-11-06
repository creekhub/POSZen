<?php

namespace Tests\Feature;

use App\Livewire\ReportSalesTrend;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ReportSalesTrendTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-05 12:00:00');

        Schema::create('Document', function (Blueprint $table): void {
            $table->increments('Id');
            $table->integer('DocumentTypeId');
            $table->dateTime('DateCreated');
            $table->decimal('Total', 12, 2);
            $table->decimal('Discount', 12, 2)->default(0);
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
            $table->decimal('Cost', 12, 2)->nullable();
            $table->decimal('LastPurchasePrice', 12, 2)->nullable();
        });

        DB::table('Product')->insert(['Id' => 1, 'Cost' => 50, 'LastPurchasePrice' => 50]);
        $this->addSale(1, '2026-01-15 10:00:00', 100, 50);
        $this->addSale(2, '2026-02-10 11:00:00', 200, 100);
        $this->addSale(3, '2026-11-03 14:00:00', 400, 200);
        $this->addSale(4, '2026-11-04 15:00:00', 200, 100);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_daily_is_the_default_and_uses_hourly_buckets(): void
    {
        $component = Livewire::test(ReportSalesTrend::class);

        $this->assertSame('Daily', $component->get('chartPeriod'));
        $chart = $this->readChartValues($component->html(), 'sales-trend-chart');

        $this->assertCount(24, $chart);
        $this->assertSame(array_fill(0, 24, 0), $chart);
    }

    public function test_monthly_period_groups_sales_and_profit_by_day(): void
    {
        $component = Livewire::test(ReportSalesTrend::class)->set('chartPeriod', 'Monthly');
        $sales = $this->readChartValues($component->html(), 'sales-trend-chart');
        $profits = $this->readChartValues($component->html(), 'sales-profit-trend-chart');

        $this->assertCount(30, $sales);
        $this->assertEquals(400, $sales[2]);
        $this->assertEquals(200, $sales[3]);
        $this->assertEquals(200, $profits[2]);
        $this->assertEquals(100, $profits[3]);
    }

    public function test_yearly_period_groups_sales_and_profit_by_month(): void
    {
        $component = Livewire::test(ReportSalesTrend::class)->set('chartPeriod', 'Yearly');
        $sales = $this->readChartValues($component->html(), 'sales-trend-chart');
        $profits = $this->readChartValues($component->html(), 'sales-profit-trend-chart');

        $this->assertCount(12, $sales);
        $this->assertEquals(100, $sales[0]);
        $this->assertEquals(200, $sales[1]);
        $this->assertEquals(600, $sales[10]);
        $this->assertEquals(50, $profits[0]);
        $this->assertEquals(100, $profits[1]);
        $this->assertEquals(300, $profits[10]);
    }

    private function addSale(int $id, string $dateCreated, float $total, float $cost): void
    {
        DB::table('Document')->insert([
            'Id' => $id,
            'DocumentTypeId' => 2,
            'DateCreated' => $dateCreated,
            'Total' => $total,
            'Discount' => 0,
        ]);
        DB::table('DocumentItem')->insert([
            'DocumentId' => $id,
            'ProductId' => 1,
            'Quantity' => 1,
            'TotalAfterDocumentDiscount' => $total,
            'Total' => $total,
            'PriceAfterDiscount' => $total,
            'ProductCost' => $cost,
        ]);
    }

    private function readChartValues(string $html, string $chartId): array
    {
        preg_match('/<canvas id="'.preg_quote($chartId, '/').'"[^>]*data-values="([^"]*)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? '[]', ENT_QUOTES), true, flags: JSON_THROW_ON_ERROR);
    }
}
