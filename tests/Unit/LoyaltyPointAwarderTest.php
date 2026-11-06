<?php

namespace Tests\Unit;

use App\Services\LoyaltyPointAwarder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoyaltyPointAwarderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('LoyaltyCard', function (Blueprint $table): void {
            $table->increments('Id');
            $table->unsignedBigInteger('CustomerId');
            $table->string('CardNumber');
            $table->decimal('PointsBalance', 10, 2)->nullable()->default(0);
            $table->string('Status')->default('ACTIVE');
        });
        Schema::create('LoyaltyRule', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('RuleName');
            $table->unsignedBigInteger('PaymentTypeId');
            $table->decimal('SpendThreshold', 10, 2);
            $table->decimal('PointsEarned', 10, 2);
            $table->dateTime('CreatedAt')->nullable();
        });
        Schema::create('LoyaltyTransaction', function (Blueprint $table): void {
            $table->increments('Id');
            $table->unsignedBigInteger('LoyaltyCardNumber');
            $table->string('DocumentNumber')->nullable();
            $table->decimal('AmountSpent', 10, 2)->nullable();
            $table->decimal('PointsChanged', 10, 2);
            $table->string('TransactionType');
            $table->unsignedBigInteger('CashierID')->nullable();
            $table->unsignedBigInteger('AdminID')->nullable();
            $table->text('Notes')->nullable();
            $table->dateTime('CreatedAt')->nullable();
            $table->unique(['LoyaltyCardNumber', 'DocumentNumber']);
        });
    }

    public function test_awards_split_payments_once_using_the_highest_eligible_rule(): void
    {
        DB::table('LoyaltyCard')->insert([
            'CustomerId' => 25,
            'CardNumber' => '1234567890',
            'PointsBalance' => null,
            'Status' => 'ACTIVE',
        ]);
        DB::table('LoyaltyRule')->insert([
            ['RuleName' => 'Base cash', 'PaymentTypeId' => 2, 'SpendThreshold' => 100, 'PointsEarned' => 1, 'CreatedAt' => now()],
            ['RuleName' => 'Premium cash', 'PaymentTypeId' => 2, 'SpendThreshold' => 150, 'PointsEarned' => 1.5, 'CreatedAt' => now()],
            ['RuleName' => 'Card payment', 'PaymentTypeId' => 3, 'SpendThreshold' => 50, 'PointsEarned' => 2, 'CreatedAt' => now()],
        ]);
        $payments = collect([
            ['payment_type_id' => 2, 'amount' => 100, 'type' => ['name' => 'Cash']],
            ['payment_type_id' => 2, 'amount' => 50, 'type' => ['name' => 'Cash']],
            ['payment_type_id' => 3, 'amount' => 100, 'type' => ['name' => 'Card']],
        ]);

        $awarder = app(LoyaltyPointAwarder::class);
        $earnedPoints = $awarder->awardForCustomer(25, '26-200-000001', $payments);
        $duplicateAward = $awarder->awardForCustomer(25, '26-200-000001', $payments);

        $this->assertEquals(5.5, $earnedPoints);
        $this->assertSame(0.0, $duplicateAward);
        $this->assertEquals(5.5, DB::table('LoyaltyCard')->value('PointsBalance'));
        $this->assertSame(1, DB::table('LoyaltyTransaction')->count());
        $this->assertEquals(250, DB::table('LoyaltyTransaction')->value('AmountSpent'));
    }

    public function test_walk_in_sales_do_not_earn_loyalty_points(): void
    {
        DB::table('LoyaltyCard')->insert([
            'CustomerId' => 1,
            'CardNumber' => '1234567890',
            'PointsBalance' => 0,
            'Status' => 'ACTIVE',
        ]);

        $earnedPoints = app(LoyaltyPointAwarder::class)->awardForCustomer(
            1,
            '26-200-000002',
            collect([['payment_type_id' => 2, 'amount' => 100, 'type' => ['name' => 'Cash']]]),
        );

        $this->assertSame(0.0, $earnedPoints);
        $this->assertSame(0, DB::table('LoyaltyTransaction')->count());
    }
}
