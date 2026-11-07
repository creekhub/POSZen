<?php

namespace Tests\Feature;

use App\Livewire\Customers;
use App\Livewire\Products;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SortableDirectoryTablesTest extends TestCase
{
    private const ALPHA_CUSTOMER = 'Alpha Customer';

    private const BETA_CUSTOMER = 'Beta Customer';

    private const ZEBRA_PRODUCT = 'Zebra Product';

    private const APPLE_PRODUCT = 'Apple Product';

    private const PRODUCT_BEYOND_FIRST_PAGE = 'ZZZ product beyond the first page';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('Customer', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('Name');
            $table->string('Code')->nullable();
            $table->string('Email')->nullable();
            $table->string('PhoneNumber')->nullable();
            $table->string('City')->nullable();
            $table->boolean('IsEnabled')->default(true);
            $table->boolean('IsCustomer')->default(true);
            $table->decimal('LoyaltyCardPoints')->default(0);
        });
        Schema::create('Document', function (Blueprint $table): void {
            $table->increments('Id');
            $table->unsignedBigInteger('CustomerId')->nullable();
            $table->string('Number')->nullable();
            $table->date('Date')->nullable();
            $table->decimal('Total', 12, 2)->default(0);
            $table->integer('DocumentTypeId')->default(2);
            $table->integer('PaidStatus')->default(1);
        });
        Schema::create('Payment', function (Blueprint $table): void {
            $table->increments('Id');
            $table->unsignedBigInteger('DocumentId');
            $table->unsignedBigInteger('PaymentTypeId');
            $table->decimal('Amount', 12, 2)->default(0);
        });
        Schema::create('PaymentType', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('Name');
            $table->boolean('IsEnabled')->default(true);
            $table->boolean('MarkAsPaid')->default(true);
            $table->integer('Ordinal')->default(0);
        });
        Schema::create('Product', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('Name');
            $table->string('Code')->nullable();
            $table->decimal('Price', 12, 2)->default(0);
            $table->decimal('Cost', 12, 2)->default(0);
            $table->boolean('IsEnabled')->default(true);
        });
        Schema::create('Barcode', function (Blueprint $table): void {
            $table->increments('Id');
            $table->unsignedBigInteger('ProductId');
            $table->string('Value');
        });

        DB::table('PaymentType')->insert(['Id' => 1, 'Name' => 'Cash']);
        DB::table('Customer')->insert([
            ['Id' => 1, 'Name' => self::ALPHA_CUSTOMER, 'Code' => 'C-100', 'Email' => 'alpha@example.test', 'PhoneNumber' => '111', 'City' => 'North', 'IsEnabled' => true, 'IsCustomer' => true, 'LoyaltyCardPoints' => 10],
            ['Id' => 2, 'Name' => self::BETA_CUSTOMER, 'Code' => 'C-200', 'Email' => 'beta@example.test', 'PhoneNumber' => '222', 'City' => 'South', 'IsEnabled' => true, 'IsCustomer' => true, 'LoyaltyCardPoints' => 20],
        ]);
        DB::table('Document')->insert([
            ['Id' => 1, 'CustomerId' => 1, 'Number' => 'D-1', 'Date' => '2026-01-01', 'Total' => 100, 'DocumentTypeId' => 2, 'PaidStatus' => 1],
            ['Id' => 2, 'CustomerId' => 2, 'Number' => 'D-2', 'Date' => '2026-01-02', 'Total' => 100, 'DocumentTypeId' => 2, 'PaidStatus' => 1],
        ]);
        DB::table('Payment')->insert([
            ['DocumentId' => 1, 'PaymentTypeId' => 1, 'Amount' => 20],
            ['DocumentId' => 2, 'PaymentTypeId' => 1, 'Amount' => 70],
        ]);
        DB::table('Product')->insert([
            ['Id' => 1, 'Name' => self::ZEBRA_PRODUCT, 'Code' => 'Z-1', 'Price' => 5, 'Cost' => 10, 'IsEnabled' => true],
            ['Id' => 2, 'Name' => self::APPLE_PRODUCT, 'Code' => 'A-1', 'Price' => 30, 'Cost' => 5, 'IsEnabled' => false],
        ]);
        DB::table('Barcode')->insert([
            ['ProductId' => 1, 'Value' => '12345'],
            ['ProductId' => 1, 'Value' => '12346'],
        ]);
    }

    public function test_customers_can_sort_by_name_and_outstanding_balance(): void
    {
        $component = Livewire::test(Customers::class);
        $component->assertSeeInOrder([self::ALPHA_CUSTOMER, self::BETA_CUSTOMER]);

        $component->call('sortBy', 'Name')
            ->assertSeeInOrder([self::BETA_CUSTOMER, self::ALPHA_CUSTOMER]);

        $component->call('sortBy', 'Outstanding')
            ->assertSeeInOrder([self::BETA_CUSTOMER, self::ALPHA_CUSTOMER]);
    }

    public function test_products_can_sort_by_name_and_price_and_barcode_search_is_not_duplicated(): void
    {
        $component = Livewire::test(Products::class);
        $component->assertSeeInOrder([self::APPLE_PRODUCT, self::ZEBRA_PRODUCT]);

        $component->call('sortBy', 'Price')
            ->assertSeeInOrder([self::ZEBRA_PRODUCT, self::APPLE_PRODUCT]);

        $component->set('search', '1234')
            ->assertSee(self::ZEBRA_PRODUCT)
            ->assertDontSee(self::APPLE_PRODUCT);
        $this->assertSame(1, substr_count($component->html(), self::ZEBRA_PRODUCT));
    }

    public function test_product_catalog_print_includes_products_beyond_the_current_page(): void
    {
        $products = [];

        for ($id = 3; $id <= 22; $id++) {
            $products[] = [
                'Id' => $id,
                'Name' => 'Product '.str_pad((string) ($id - 2), 2, '0', STR_PAD_LEFT),
                'Code' => 'P-'.$id,
                'Price' => 10 + $id,
                'Cost' => 5,
                'IsEnabled' => true,
            ];
        }

        DB::table('Product')->insert($products);
        DB::table('Product')->insert([
            'Id' => 23,
            'Name' => self::PRODUCT_BEYOND_FIRST_PAGE,
            'Code' => 'ZZZ-1',
            'Price' => 42.50,
            'Cost' => 12.00,
            'IsEnabled' => true,
        ]);

        $component = Livewire::test(Products::class)
            ->assertDontSee(self::PRODUCT_BEYOND_FIRST_PAGE)
            ->call('prepareCatalogPrint')
            ->assertSee(self::PRODUCT_BEYOND_FIRST_PAGE)
            ->assertSee('Selling Price')
            ->assertSee('42.50')
            ->assertSee('catalog-print-thermal');

        $component->call('finishCatalogPrint');

        Livewire::test(Products::class)
            ->call('prepareCatalogPrint', 'a4')
            ->assertSee('catalog-print-a4');
    }
}
