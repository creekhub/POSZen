<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportSalesTrend extends Component
{
    public string $chartPeriod = 'Weekly';
    public string $chartStartDate = '';
    public string $chartEndDate = '';

    public function mount(): void
    {
        $today = Carbon::today();
        $this->chartStartDate = $today->copy()->subDays(6)->toDateString();
        $this->chartEndDate = $today->toDateString();
    }

    public function updatedChartPeriod(string $period): void
    {
        $today = Carbon::today();

        [$startDate, $endDate] = match ($period) {
            'Daily' => [$today, $today],
            'Monthly' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'Yearly' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            default => [$today->copy()->subDays(6), $today],
        };

        $this->chartStartDate = $startDate->toDateString();
        $this->chartEndDate = $endDate->toDateString();
    }

    public function render(): View
    {
        $salesChart = $this->buildSalesChart();
        $averageLabel = [
            'Daily' => 'Hourly',
            'Weekly' => 'Daily',
            'Monthly' => 'Daily',
            'Yearly' => 'Monthly',
        ][$this->chartPeriod] ?? 'Daily';

        return view('livewire.report-sales-trend', [
            'salesChart' => $salesChart,
            'salesAverage' => $salesChart['total'] / max(1, count($salesChart['points'])),
            'averageLabel' => $averageLabel,
        ]);
    }

    private function buildSalesChart(): array
    {
        $startDate = Carbon::parse($this->chartStartDate ?: Carbon::today()->toDateString())->startOfDay();
        $endDate = Carbon::parse($this->chartEndDate ?: $startDate->toDateString())->endOfDay();

        if ($endDate->lessThan($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $sales = DB::table('Document')
            ->where('DocumentTypeId', 2)
            ->whereBetween('DateCreated', [$startDate, $endDate])
            ->get(['DateCreated', 'Total']);

        [$labels, $values] = match ($this->chartPeriod) {
            'Daily' => $this->buildHourlyValues($sales),
            'Yearly' => $this->buildMonthlyValues($sales, $startDate, $endDate),
            default => $this->buildDailyValues($sales, $startDate, $endDate),
        };

        $maximum = max($values ?: [0]);

        return [
            'points' => collect($values)->map(fn (float $value, int $index): array => [
                'label' => $labels[$index],
                'value' => $value,
                'height' => $maximum > 0 ? max(4, ($value / $maximum) * 100) : 4,
            ])->all(),
            'total' => array_sum($values),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ];
    }

    private function buildHourlyValues($sales): array
    {
        $selectedDate = Carbon::parse($this->chartStartDate ?: Carbon::today()->toDateString());
        $values = array_fill(0, 24, 0.0);
        $labels = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $labels[] = Carbon::createFromTime($hour)->format('g A');
        }

        foreach ($sales as $sale) {
            $saleDate = Carbon::parse($sale->DateCreated);

            if ($saleDate->isSameDay($selectedDate)) {
                $values[$saleDate->hour] += (float) $sale->Total;
            }
        }

        return [$labels, $values];
    }

    private function buildMonthlyValues($sales, Carbon $startDate, Carbon $endDate): array
    {
        $month = $startDate->copy()->startOfMonth();
        $lastMonth = $endDate->copy()->startOfMonth();
        $values = [];
        $labels = [];

        while ($month->lessThanOrEqualTo($lastMonth)) {
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $values[$key] = 0.0;
            $month->addMonth();
        }

        foreach ($sales as $sale) {
            $key = Carbon::parse($sale->DateCreated)->format('Y-m');

            if (array_key_exists($key, $values)) {
                $values[$key] += (float) $sale->Total;
            }
        }

        return [$labels, array_values($values)];
    }

    private function buildDailyValues($sales, Carbon $startDate, Carbon $endDate): array
    {
        $day = $startDate->copy()->startOfDay();
        $lastDay = $endDate->copy()->startOfDay();
        $values = [];
        $labels = [];

        while ($day->lessThanOrEqualTo($lastDay)) {
            $key = $day->toDateString();
            $labels[] = $day->format('M j');
            $values[$key] = 0.0;
            $day->addDay();
        }

        foreach ($sales as $sale) {
            $key = Carbon::parse($sale->DateCreated)->toDateString();

            if (array_key_exists($key, $values)) {
                $values[$key] += (float) $sale->Total;
            }
        }

        return [$labels, array_values($values)];
    }
}

