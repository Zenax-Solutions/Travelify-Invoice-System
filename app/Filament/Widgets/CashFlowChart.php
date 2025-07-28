<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\VendorPayment;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class CashFlowChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Cash Flow Analysis';
    protected static ?string $description = 'Revenue vs Expenses over the last 12 months';
    protected static ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 2;
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $months = collect();
        $revenues = collect();
        $expenses = collect();
        $profits = collect();

        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->format('M Y');

            // Calculate revenue (payments received)
            $revenue = Payment::whereYear('payment_date', $month->year)
                ->whereMonth('payment_date', $month->month)
                ->sum('amount');

            // Calculate expenses (vendor payments)
            $expense = VendorPayment::whereYear('payment_date', $month->year)
                ->whereMonth('payment_date', $month->month)
                ->sum('amount');

            $profit = $revenue - $expense;

            $months->push($monthName);
            $revenues->push($revenue);
            $expenses->push($expense);
            $profits->push($profit);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (Income)',
                    'data' => $revenues->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Expenses (Outgoing)',
                    'data' => $expenses->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Net Profit',
                    'data' => $profits->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'type' => 'line',
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rs " + value.toLocaleString(); }',
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": Rs " + context.parsed.y.toLocaleString(); }',
                    ],
                ],
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ];
    }
}
