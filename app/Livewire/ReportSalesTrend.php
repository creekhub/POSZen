<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportSalesTrend extends Component
{
    public string $chartPeriod = 'Daily';

    public string $chartStartDate = '';

    public string $chartEndDate = '';

    private ?array $salesChartCache = null;

    public function mount(): void
    {
        $today = Carbon::today();
        $this->chartStartDate = $today->toDateString();
        $this->chartEndDate = $today->toDateString();
        $this->salesChartCache = $this->buildSalesChart();
    }

    public function updatedChartPeriod(string $period): void
    {
        $today = Carbon::today();
        [$startDate, $endDate] = match ($period) {
            'Daily' => [$today, $today],
            'Weekly' => [$today->copy()->subDays(6), $today],
            'Monthly' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'Yearly' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            default => [$today, $today],
        };

        $this->chartStartDate = $startDate->toDateString();
        $this->chartEndDate = $endDate->toDateString();
        $this->refreshSalesChart();
    }

    public function updatedChartStartDate(): void
    {
        $this->refreshSalesChart();
    }

    public function updatedChartEndDate(): void
    {
        $this->refreshSalesChart();
    }

    public function render(): View
    {
        $salesChart = $this->salesChartCache ??= $this->buildSalesChart();
        $averageLabel = [
            'Daily' => 'Hourly',
            'Weekly' => 'Daily',
            'Monthly' => 'Daily',
            'Yearly' => 'Monthly',
        ][$this->chartPeriod] ?? 'Daily';

        return view('livewire.report-sales-trend', [
            'salesChart' => $salesChart,
            'salesAverage' => $salesChart['total'] / max(1, count($salesChart['values'])),
            'averageLabel' => $averageLabel,
        ]);
    }

    private function refreshSalesChart(): void
    {
        $this->salesChartCache = $this->buildSalesChart();
        $this->dispatch(
            'sales-trend-updated',
            labels: $this->salesChartCache['labels'],
            salesValues: $this->salesChartCache['values'],
            profitValues: $this->salesChartCache['profitValues'],
        );
    }

    private function buildSalesChart(): array
    {
        $startDate = Carbon::parse($this->chartStartDate ?: Carbon::today()->toDateString())->startOfDay();
        $endDate = Carbon::parse($this->chartEndDate ?: $startDate->toDateString())->endOfDay();

        if ($endDate->lessThan($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $queryStart = $startDate->copy();
        $queryEnd = $endDate->copy();
        if ($this->chartPeriod === 'Daily') {
            $queryStart = Carbon::parse($this->chartStartDate ?: Carbon::today()->toDateString())->startOfDay();
            $queryEnd = $queryStart->copy()->endOfDay();
        }

        $salesBucket = $this->bucketExpression('DateCreated');
        $sales = DB::table('Document')
            ->selectRaw($salesBucket.' as bucket, COALESCE(SUM(Total), 0) as total')
            ->where('DocumentTypeId', 2)
            ->whereBetween('DateCreated', [$queryStart, $queryEnd])
            ->groupByRaw($salesBucket)
            ->get()
            ->keyBy('bucket');

        $documentSubtotals = DB::table('DocumentItem as discount_item')
            ->join('Document as subtotal_document', 'subtotal_document.Id', '=', 'discount_item.DocumentId')
            ->where('subtotal_document.DocumentTypeId', 2)
            ->whereBetween('subtotal_document.DateCreated', [$queryStart, $queryEnd])
            ->select('discount_item.DocumentId')
            ->selectRaw('COALESCE(SUM(COALESCE(discount_item.TotalAfterDocumentDiscount, discount_item.Total, discount_item.PriceAfterDiscount * discount_item.Quantity)), 0) as subtotal')
            ->groupBy('discount_item.DocumentId');
        $lineAmount = 'COALESCE(item.TotalAfterDocumentDiscount, item.Total, item.PriceAfterDiscount * item.Quantity)';
        $documentSubtotal = 'COALESCE(document_subtotal.subtotal, 0)';
        $effectiveOrderDiscount = '(CASE WHEN COALESCE(document.Discount, 0) > '.$documentSubtotal.' THEN '.$documentSubtotal.' ELSE COALESCE(document.Discount, 0) END)';
        $orderDiscountAllocation = '('.$effectiveOrderDiscount.' * '.$lineAmount.' / NULLIF('.$documentSubtotal.', 0))';
        $netAmount = '(CASE WHEN '.$lineAmount.' <= '.$orderDiscountAllocation.' THEN 0 ELSE '.$lineAmount.' - '.$orderDiscountAllocation.' END)';
        $productCost = 'COALESCE(NULLIF(item.ProductCost, 0), NULLIF(product.Cost, 0), product.LastPurchasePrice, 0)';
        $profitBucket = $this->bucketExpression('document.DateCreated');

        $profits = DB::table('DocumentItem as item')
            ->join('Document as document', 'document.Id', '=', 'item.DocumentId')
            ->join('Product as product', 'product.Id', '=', 'item.ProductId')
            ->leftJoinSub($documentSubtotals, 'document_subtotal', 'document_subtotal.DocumentId', '=', 'document.Id')
            ->where('document.DocumentTypeId', 2)
            ->whereBetween('document.DateCreated', [$queryStart, $queryEnd])
            ->whereRaw($productCost.' > 0')
            ->selectRaw($profitBucket.' as bucket, COALESCE(SUM(('.$netAmount.') - ('.$productCost.') * item.Quantity), 0) as total')
            ->groupByRaw($profitBucket)
            ->get()
            ->keyBy('bucket');

        [$labels, $values] = $this->buildBucketValues($sales, $startDate, $endDate);
        [, $profitValues] = $this->buildBucketValues($profits, $startDate, $endDate);

        return [
            'labels' => $labels,
            'values' => $values,
            'total' => array_sum($values),
            'profitValues' => $profitValues,
            'profitTotal' => array_sum($profitValues),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ];
    }

    private function bucketExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($this->chartPeriod) {
            'Daily' => $driver === 'sqlite' ? "strftime('%H', {$column})" : "HOUR({$column})",
            'Yearly' => $driver === 'sqlite' ? "strftime('%Y-%m', {$column})" : "DATE_FORMAT({$column}, '%Y-%m')",
            default => "DATE({$column})",
        };
    }

    private function buildBucketValues($aggregates, Carbon $startDate, Carbon $endDate): array
    {
        $keys = [];
        $labels = [];

        if ($this->chartPeriod === 'Daily') {
            for ($hour = 0; $hour < 24; $hour++) {
                $keys[] = sprintf('%02d', $hour);
                $labels[] = Carbon::createFromTime($hour)->format('g A');
            }
        } elseif ($this->chartPeriod === 'Yearly') {
            $month = $startDate->copy()->startOfMonth();
            $lastMonth = $endDate->copy()->startOfMonth();

            while ($month->lessThanOrEqualTo($lastMonth)) {
                $keys[] = $month->format('Y-m');
                $labels[] = $month->format('M Y');
                $month->addMonth();
            }
        } else {
            $day = $startDate->copy()->startOfDay();
            $lastDay = $endDate->copy()->startOfDay();

            while ($day->lessThanOrEqualTo($lastDay)) {
                $keys[] = $day->toDateString();
                $labels[] = $day->format('M j');
                $day->addDay();
            }
        }

        $aggregatedValues = [];
        foreach ($aggregates as $aggregate) {
            $key = (string) $aggregate->bucket;
            if ($this->chartPeriod === 'Daily') {
                $key = str_pad($key, 2, '0', STR_PAD_LEFT);
            }
            $aggregatedValues[$key] = (float) $aggregate->total;
        }

        $values = [];
        foreach ($keys as $key) {
            $values[] = $aggregatedValues[$key] ?? 0.0;
        }

        return [$labels, $values];
    }
}
