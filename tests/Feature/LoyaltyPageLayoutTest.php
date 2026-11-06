<?php

namespace Tests\Feature;

use App\Livewire\LoyaltyCardModule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class LoyaltyPageLayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('Customer', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('Name');
            $table->string('Code')->nullable();
            $table->boolean('IsCustomer')->default(true);
            $table->boolean('IsEnabled')->default(true);
            $table->decimal('LoyaltyCardPoints')->default(0);
        });
        Schema::create('LoyaltyCard', function (Blueprint $table): void {
            $table->increments('Id');
            $table->unsignedBigInteger('CustomerId');
            $table->string('CardNumber')->nullable();
            $table->decimal('PointsBalance')->nullable();
            $table->string('Status')->nullable();
        });
        Schema::create('LoyaltyRule', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('RuleName');
            $table->unsignedBigInteger('PaymentTypeId');
            $table->decimal('SpendThreshold');
            $table->decimal('PointsEarned');
            $table->dateTime('CreatedAt')->nullable();
        });
        Schema::create('PaymentType', function (Blueprint $table): void {
            $table->increments('Id');
            $table->string('Name');
            $table->boolean('IsEnabled')->default(true);
            $table->integer('Ordinal')->default(0);
        });
        Schema::create('LoyaltyTransaction', function (Blueprint $table): void {
            $table->increments('Id');
            $table->unsignedBigInteger('LoyaltyCardNumber');
            $table->string('DocumentNumber')->nullable();
            $table->decimal('AmountSpent')->nullable();
            $table->decimal('PointsChanged');
            $table->string('TransactionType');
            $table->dateTime('CreatedAt')->nullable();
        });
    }

    public function test_all_loyalty_tabs_render_inside_the_shared_app_layout(): void
    {
        $user = new User;
        $user->setAttribute('Id', 1);
        $this->actingAs($user);

        foreach ([
            'loyalty.overview' => 'Recent activity',
            'loyalty.cards' => 'Issue a loyalty card',
            'loyalty.rules' => 'Add earning rule',
            'loyalty.transactions' => 'Transaction history',
        ] as $routeName => $tabContent) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('<!DOCTYPE html>', false)
                ->assertSee('wire:id=', false)
                ->assertSee('<nav aria-label="Main navigation"', false)
                ->assertSee('Loyalty Card')
                ->assertSee($tabContent);
        }
    }

    public function test_pre_generated_ean13_card_number_is_unique_and_validated_when_issued(): void
    {
        $user = new User;
        $user->setAttribute('Id', 1);
        $this->actingAs($user);

        DB::table('Customer')->insert([
            ['Id' => 50, 'Name' => 'First customer', 'Code' => 'C050', 'IsCustomer' => true, 'IsEnabled' => true, 'LoyaltyCardPoints' => 0],
            ['Id' => 51, 'Name' => 'Second customer', 'Code' => 'C051', 'IsCustomer' => true, 'IsEnabled' => true, 'LoyaltyCardPoints' => 0],
        ]);

        $component = Livewire::actingAs($user)
            ->test(LoyaltyCardModule::class)
            ->call('setTab', 'cards')
            ->call('preGenerateCardNumber')
            ->assertDispatched('loyalty-card-number-generated')
            ->assertSet('activeTab', 'cards')
            ->assertHasNoErrors();
        $cardNumber = (string) $component->get('cardNumber');

        $this->assertMatchesRegularExpression('/^4800'.now()->format('Y').'\d{5}$/', $cardNumber);
        $this->assertSame($this->ean13CheckDigit($cardNumber), substr($cardNumber, -1));
        $this->assertDatabaseMissing('LoyaltyCard', ['CardNumber' => $cardNumber]);

        $component->set('customerId', '50')
            ->call('issueCard')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('LoyaltyCard', ['CustomerId' => 50, 'CardNumber' => $cardNumber]);

        Livewire::actingAs($user)
            ->test(LoyaltyCardModule::class)
            ->set('customerId', '51')
            ->set('cardNumber', $cardNumber)
            ->call('issueCard')
            ->assertHasErrors(['cardNumber']);
    }

    private function ean13CheckDigit(string $cardNumber): string
    {
        $sum = 0;
        foreach (str_split(substr($cardNumber, 0, 12)) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }
}
