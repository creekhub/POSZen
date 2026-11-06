<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class LoyaltyCardModule extends Component
{
    use WithPagination;

    private const CARD_TABLE = 'LoyaltyCard as card';

    private const CUSTOMER_TABLE = 'Customer as customer';

    public string $activeTab = 'overview';

    public string $actionMessage = '';

    public string $customerId = '';

    public string $cardNumber = '';

    public ?int $editingRuleId = null;

    public string $ruleName = '';

    public string $paymentTypeId = '';

    public string $spendThreshold = '';

    public string $pointsEarned = '';

    public string $filterCardNumber = '';

    public string $filterFrom = '';

    public string $filterTo = '';

    public function mount(): void
    {
        $this->activeTab = match (request()->route()?->getName()) {
            'loyalty.cards' => 'cards',
            'loyalty.rules' => 'rules',
            'loyalty.transactions' => 'transactions',
            default => 'overview',
        };
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['overview', 'cards', 'rules', 'transactions'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->actionMessage = '';
        $this->resetPage();
        $this->resetValidation();
    }

    public function issueCard(): void
    {
        $validated = $this->validate([
            'customerId' => ['required', 'integer', 'exists:Customer,Id'],
            'cardNumber' => [
                'nullable',
                'string',
                Rule::unique('LoyaltyCard', 'CardNumber'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $prefix = '4800'.now()->format('Y');
                    $body = substr((string) $value, 0, 12);

                    if (! preg_match('/^4800\d{9}$/', (string) $value)
                        || ! str_starts_with((string) $value, $prefix)
                        || ! ctype_digit((string) $value)
                        || ! hash_equals($this->ean13CheckDigit($body), substr((string) $value, 12, 1))) {
                        $fail('Enter a valid EAN-13 code generated for the current year.');
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($validated): void {
            $customer = DB::table('Customer')
                ->where('Id', $validated['customerId'])
                ->where('IsCustomer', 1)
                ->where('IsEnabled', 1)
                ->lockForUpdate()
                ->first(['Id']);

            if (! $customer) {
                throw ValidationException::withMessages(['customerId' => 'Select an enabled customer.']);
            }

            if (DB::table('LoyaltyCard')->where('CustomerId', $customer->Id)->exists()) {
                throw ValidationException::withMessages(['customerId' => 'This customer already has a loyalty card.']);
            }

            $cardNumber = $validated['cardNumber'] ?: $this->generateUniqueCardNumber();
            if (DB::table('LoyaltyCard')->where('CardNumber', $cardNumber)->exists()) {
                throw ValidationException::withMessages(['cardNumber' => 'This card number is already in use. Generate another code.']);
            }

            DB::table('LoyaltyCard')->insert([
                'CustomerId' => $customer->Id,
                'CardNumber' => $cardNumber,
                'PointsBalance' => 0,
                'Status' => 'ACTIVE',
            ]);
        });

        $this->customerId = '';
        $this->cardNumber = '';
        $this->actionMessage = 'Loyalty card issued.';
    }

    public function preGenerateCardNumber(): void
    {
        $this->activeTab = 'cards';
        $this->cardNumber = $this->generateUniqueCardNumber();
        $this->actionMessage = 'Unique EAN-13 card number generated.';
        $this->resetValidation('cardNumber');
        $this->dispatch('loyalty-card-number-generated', code: $this->cardNumber);
    }

    public function updateCardStatus(int $cardId, string $status): void
    {
        $validated = validator(['status' => $status], [
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ])->validate();
        $updated = DB::table('LoyaltyCard')->where('Id', $cardId)->update(['Status' => $validated['status']]);

        if (! $updated && ! DB::table('LoyaltyCard')->where('Id', $cardId)->exists()) {
            abort(404);
        }

        $this->actionMessage = 'Loyalty card status updated.';
    }

    public function createRule(): void
    {
        $validated = $this->validateRule();
        DB::table('LoyaltyRule')->insert([
            'RuleName' => trim($validated['ruleName']),
            'PaymentTypeId' => $validated['paymentTypeId'],
            'SpendThreshold' => $validated['spendThreshold'],
            'PointsEarned' => $validated['pointsEarned'],
            'CreatedAt' => now(),
        ]);

        $this->resetRuleFields();
        $this->actionMessage = 'Loyalty rule created.';
    }

    public function editRule(int $ruleId): void
    {
        $rule = DB::table('LoyaltyRule')->where('Id', $ruleId)->first();
        abort_unless($rule, 404);

        $this->editingRuleId = (int) $rule->Id;
        $this->ruleName = (string) $rule->RuleName;
        $this->paymentTypeId = (string) $rule->PaymentTypeId;
        $this->spendThreshold = (string) $rule->SpendThreshold;
        $this->pointsEarned = (string) $rule->PointsEarned;
        $this->resetValidation();
    }

    public function updateRule(): void
    {
        abort_unless($this->editingRuleId, 404);
        $validated = $this->validateRule();
        $updated = DB::table('LoyaltyRule')->where('Id', $this->editingRuleId)->update([
            'RuleName' => trim($validated['ruleName']),
            'PaymentTypeId' => $validated['paymentTypeId'],
            'SpendThreshold' => $validated['spendThreshold'],
            'PointsEarned' => $validated['pointsEarned'],
        ]);

        if (! $updated && ! DB::table('LoyaltyRule')->where('Id', $this->editingRuleId)->exists()) {
            abort(404);
        }

        $this->resetRuleFields();
        $this->actionMessage = 'Loyalty rule updated.';
    }

    public function cancelRuleEdit(): void
    {
        $this->resetRuleFields();
    }

    public function deleteRule(int $ruleId): void
    {
        abort_unless(DB::table('LoyaltyRule')->where('Id', $ruleId)->delete(), 404);
        $this->actionMessage = 'Loyalty rule deleted.';
    }

    public function applyTransactionFilters(): void
    {
        $this->validate([
            'filterCardNumber' => ['nullable', 'digits_between:8,18'],
            'filterFrom' => ['nullable', 'date'],
            'filterTo' => ['nullable', 'date', 'after_or_equal:filterFrom'],
        ]);

        $this->resetPage();
    }

    public function clearTransactionFilters(): void
    {
        $this->filterCardNumber = '';
        $this->filterFrom = '';
        $this->filterTo = '';
        $this->resetPage();
        $this->resetValidation();
    }

    public function render(): View
    {
        $viewData = ['activeTab' => $this->activeTab];

        if ($this->activeTab === 'overview') {
            $viewData += [
                'cardCount' => DB::table('LoyaltyCard')->count(),
                'activeCardCount' => DB::table('LoyaltyCard')->where('Status', 'ACTIVE')->count(),
                'ruleCount' => DB::table('LoyaltyRule')->count(),
                'pointsBalance' => (float) DB::table('LoyaltyCard')->where('Status', 'ACTIVE')->sum('PointsBalance'),
                'recentTransactions' => $this->transactionQuery()->orderByDesc('loyalty_transaction.CreatedAt')->limit(10)->get(),
            ];
        } elseif ($this->activeTab === 'cards') {
            $viewData += [
                'cards' => DB::table(self::CARD_TABLE)
                    ->join(self::CUSTOMER_TABLE, 'customer.Id', '=', 'card.CustomerId')
                    ->orderByDesc('card.Id')
                    ->get([
                        'card.Id', 'card.CardNumber', 'customer.LoyaltyCardPoints as PointsBalance', 'card.Status',
                        'customer.Id as CustomerId', 'customer.Name as CustomerName', 'customer.Code as CustomerCode',
                    ]),
                'availableCustomers' => DB::table(self::CUSTOMER_TABLE)
                    ->where('customer.IsCustomer', 1)
                    ->where('customer.IsEnabled', 1)
                    ->whereNotExists(function ($query): void {
                        $query->selectRaw('1')->from(self::CARD_TABLE)->whereColumn('card.CustomerId', 'customer.Id');
                    })
                    ->orderBy('customer.Name')
                    ->get(['customer.Id', 'customer.Name', 'customer.Code']),
            ];
        } elseif ($this->activeTab === 'rules') {
            $viewData += [
                'rules' => DB::table('LoyaltyRule as rule')
                    ->leftJoin('PaymentType as payment_type', 'payment_type.Id', '=', 'rule.PaymentTypeId')
                    ->orderBy('payment_type.Name')
                    ->orderBy('rule.SpendThreshold')
                    ->get([
                        'rule.Id', 'rule.RuleName', 'rule.PaymentTypeId', 'rule.SpendThreshold', 'rule.PointsEarned',
                        'payment_type.Name as PaymentTypeName',
                    ]),
                'paymentTypes' => $this->paymentTypes(),
            ];
        } else {
            $viewData += [
                'transactions' => $this->transactionQuery()
                    ->when($this->filterCardNumber, fn ($query) => $query->where('loyalty_transaction.LoyaltyCardNumber', $this->filterCardNumber))
                    ->when($this->filterFrom, fn ($query) => $query->whereDate('loyalty_transaction.CreatedAt', '>=', $this->filterFrom))
                    ->when($this->filterTo, fn ($query) => $query->whereDate('loyalty_transaction.CreatedAt', '<=', $this->filterTo))
                    ->orderByDesc('loyalty_transaction.CreatedAt')
                    ->paginate(25),
            ];
        }

        return view('livewire.loyalty-card-module', $viewData)->layout('layouts.app');
    }

    private function validateRule(): array
    {
        return $this->validate([
            'ruleName' => ['required', 'string', 'max:120'],
            'paymentTypeId' => ['required', 'integer', 'exists:PaymentType,Id'],
            'spendThreshold' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'pointsEarned' => ['required', 'numeric', 'gt:0', 'max:999999999'],
        ]);
    }

    private function resetRuleFields(): void
    {
        $this->editingRuleId = null;
        $this->ruleName = '';
        $this->paymentTypeId = '';
        $this->spendThreshold = '';
        $this->pointsEarned = '';
        $this->resetValidation();
    }

    private function paymentTypes()
    {
        return DB::table('PaymentType')
            ->where('IsEnabled', 1)
            ->orderBy('Ordinal')
            ->orderBy('Name')
            ->get(['Id', 'Name']);
    }

    private function generateUniqueCardNumber(): string
    {
        $prefix = '4800'.now()->format('Y');

        do {
            $body = $prefix.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $cardNumber = $body.$this->ean13CheckDigit($body);
        } while (DB::table('LoyaltyCard')->where('CardNumber', $cardNumber)->exists());

        return $cardNumber;
    }

    private function ean13CheckDigit(string $body): string
    {
        $weightedSum = 0;
        foreach (str_split($body) as $index => $digit) {
            $weightedSum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (string) ((10 - ($weightedSum % 10)) % 10);
    }

    private function transactionQuery()
    {
        return DB::table('LoyaltyTransaction as loyalty_transaction')
            ->leftJoin(self::CARD_TABLE, function ($join): void {
                $join->on(DB::raw('CAST(card.CardNumber AS CHAR)'), '=', DB::raw('CAST(loyalty_transaction.LoyaltyCardNumber AS CHAR)'));
            })
            ->leftJoin(self::CUSTOMER_TABLE, 'customer.Id', '=', 'card.CustomerId')
            ->select([
                'loyalty_transaction.*',
                'card.CardNumber as CardNumber',
                'customer.Name as CustomerName',
            ]);
    }
}
