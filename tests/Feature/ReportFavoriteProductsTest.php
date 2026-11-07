<?php

namespace Tests\Feature;

use App\Livewire\ReportFavoriteProducts;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ReportFavoriteProductsTest extends TestCase
{
    private const COFFEE_BEANS = 'Coffee Beans';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-06 12:00:00');

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
            $table->string('Name');
            $table->string('Code')->nullable();
            $table->decimal('Cost', 12, 2)->nullable();
            $table->decimal('LastPurchasePrice', 12, 2)->nullable();
        });
        Schema::create('Barcode', function (Blueprint $table): void {
            $table->increments('Id');
            $table->unsignedBigInteger('ProductId');
            $table->string('Value');
        });
        Schema::create('TrackFavoriteProducts', function (Blueprint $table): void {
            $table->increments('Id');
            $table->integer('ProductId')->nullable();
        });
        DB::table('TrackFavoriteProducts')->insert(['ProductId' => null]);

        DB::table('Product')->insert([
            ['Id' => 1, 'Name' => 'Coffee', 'Code' => 'COF', 'Cost' => 40, 'LastPurchasePrice' => 40],
            ['Id' => 2, 'Name' => 'Tea', 'Code' => 'TEA', 'Cost' => 30, 'LastPurchasePrice' => 30],
            ['Id' => 3, 'Name' => self::COFFEE_BEANS, 'Code' => 'BEAN', 'Cost' => 25, 'LastPurchasePrice' => 25],
        ]);
        DB::table('Document')->insert([
            ['Id' => 1, 'DocumentTypeId' => 2, 'DateCreated' => '2026-11-05 10:00:00', 'Total' => 100, 'Discount' => 0],
            ['Id' => 2, 'DocumentTypeId' => 2, 'DateCreated' => '2026-11-05 11:00:00', 'Total' => 50, 'Discount' => 0],
        ]);
        DB::table('DocumentItem')->insert([
            ['DocumentId' => 1, 'ProductId' => 1, 'Quantity' => 1, 'TotalAfterDocumentDiscount' => 100, 'Total' => 100, 'PriceAfterDiscount' => 100, 'ProductCost' => 40],
            ['DocumentId' => 2, 'ProductId' => 2, 'Quantity' => 1, 'TotalAfterDocumentDiscount' => 50, 'Total' => 50, 'PriceAfterDiscount' => 50, 'ProductCost' => 30],
        ]);

        $user = new User;
        $user->setAttribute('Id', 7);
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_products_can_be_added_removed_and_chart_totals_only_include_favorites(): void
    {
        $this->getJson(route('reports.favorite-products.product-options', [
            'term' => 'Coffee',
            'excludeTrackedFavorites' => 1,
        ]))->assertOk()->assertJsonCount(2, 'results');

        $component = Livewire::test(ReportFavoriteProducts::class)
            ->assertSeeHtml('id="favorite-product-search"')
            ->assertDontSeeHtml('id="favorite-product-select"')
            ->set('selectedProductIds', ['1', '3'])
            ->call('addProducts')
            ->assertDispatched('favorite-products-trend-updated', datasets: [
                ['label' => 'Coffee', 'productIndex' => 0, 'metric' => 'sales', 'data' => [0, 0, 0, 0, 0, 100, 0]],
                ['label' => 'Coffee', 'productIndex' => 0, 'metric' => 'profit', 'data' => [0, 0, 0, 0, 0, 60, 0]],
                ['label' => self::COFFEE_BEANS, 'productIndex' => 1, 'metric' => 'sales', 'data' => array_fill(0, 7, 0)],
                ['label' => self::COFFEE_BEANS, 'productIndex' => 1, 'metric' => 'profit', 'data' => array_fill(0, 7, 0)],
            ])
            ->assertSee('₱100.00')
            ->assertSee('₱60.00')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('TrackFavoriteProducts', ['ProductId' => 1]);
        $this->assertDatabaseHas('TrackFavoriteProducts', ['ProductId' => 3]);
        $this->getJson(route('reports.favorite-products.product-options', [
            'term' => 'Coffee',
            'excludeTrackedFavorites' => 1,
        ]))->assertOk()->assertJsonCount(0, 'results');
        $this->getJson(route('reports.favorite-products.product-options', ['term' => 'Coffee']))
            ->assertOk()
            ->assertJsonCount(2, 'results');

        $component->set('selectedProductIds', ['1'])
            ->call('addProducts')
            ->assertHasErrors(['selectedProductIds']);
        $this->assertSame(1, DB::table('TrackFavoriteProducts')->where('ProductId', 1)->count());

        $component->call('removeProduct', 1)
            ->assertDispatched('favorite-products-trend-updated', datasets: [
                ['label' => self::COFFEE_BEANS, 'productIndex' => 0, 'metric' => 'sales', 'data' => array_fill(0, 7, 0)],
                ['label' => self::COFFEE_BEANS, 'productIndex' => 0, 'metric' => 'profit', 'data' => array_fill(0, 7, 0)],
            ]);

        $this->assertDatabaseMissing('TrackFavoriteProducts', ['ProductId' => 1]);
    }

}
