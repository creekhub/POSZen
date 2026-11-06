<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoyaltyPointAwarder
{
    public function awardForCustomer(int $customerId, string $documentNumber, Collection $payments): float
    {
        if ($customerId <= 1 || ! Schema::hasTable('LoyaltyCard') || ! Schema::hasTable('LoyaltyRule') || ! Schema::hasTable('LoyaltyTransaction')) {
            return 0.0;
        }

        $card = DB::table('LoyaltyCard')
            ->where('CustomerId', $customerId)
            ->where('Status', 'ACTIVE')
            ->lockForUpdate()
            ->first(['Id', 'CardNumber', 'PointsBalance']);

        if (! $card || ! ctype_digit((string) $card->CardNumber) || DB::table('LoyaltyTransaction')
            ->where('LoyaltyCardNumber', (int) $card->CardNumber)
            ->where('DocumentNumber', $documentNumber)
            ->exists()) {
            return 0.0;
        }

        $cardNumber = (int) $card->CardNumber;
        $earnedPoints = 0.0;
        $eligibleSpend = 0.0;
        $awardNotes = [];

        foreach ($payments->groupBy('payment_type_id') as $paymentTypeId => $paymentGroup) {
            $amountSpent = round((float) $paymentGroup->sum('amount'), 2);
            $rule = DB::table('LoyaltyRule')
                ->where('PaymentTypeId', $paymentTypeId)
                ->where('SpendThreshold', '>', 0)
                ->where('SpendThreshold', '<=', $amountSpent)
                ->orderByDesc('SpendThreshold')
                ->first(['RuleName', 'SpendThreshold', 'PointsEarned']);

            if (! $rule || (float) $rule->PointsEarned <= 0) {
                continue;
            }

            $thresholdCount = (int) floor(($amountSpent + 0.000001) / (float) $rule->SpendThreshold);
            $points = round($thresholdCount * (float) $rule->PointsEarned, 2);

            if ($points <= 0) {
                continue;
            }

            $earnedPoints += $points;
            $eligibleSpend += $amountSpent;
            $paymentTypeName = (string) ($paymentGroup->first()['type']['name'] ?? 'payment');
            $ruleName = trim((string) $rule->RuleName) ?: 'Loyalty rule';
            $awardNotes[] = $ruleName.' ('.$paymentTypeName.')';
        }

        $earnedPoints = round($earnedPoints, 2);
        if ($earnedPoints > 0) {
            DB::table('LoyaltyCard')->where('Id', $card->Id)->update([
                'PointsBalance' => round((float) ($card->PointsBalance ?? 0) + $earnedPoints, 2),
            ]);
            DB::table('LoyaltyTransaction')->insert([
                'LoyaltyCardNumber' => $cardNumber,
                'DocumentNumber' => $documentNumber,
                'AmountSpent' => round($eligibleSpend, 2),
                'PointsChanged' => $earnedPoints,
                'TransactionType' => 'EARN',
                'CashierID' => Auth::id(),
                'Notes' => implode('; ', $awardNotes),
            ]);
        }

        return $earnedPoints;
    }
}
