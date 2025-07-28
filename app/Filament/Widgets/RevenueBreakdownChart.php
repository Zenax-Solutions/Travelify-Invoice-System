<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueBreakdownChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue by Service Category';
    protected static ?string $description = 'Revenue distribution across different services';
    protected static ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $serviceRevenue = DB::table('categories')
            ->select([
                'categories.name as category_name',
                DB::raw('SUM(invoice_service.quantity * invoice_service.unit_price) as total_revenue')
            ])
            ->join('services', 'categories.id', '=', 'services.category_id')
            ->join('invoice_service', 'services.id', '=', 'invoice_service.service_id')
            ->join('invoices', 'invoice_service.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', '!=', 'cancelled')
            ->whereYear('invoices.invoice_date', now()->year)
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->limit(8)
            ->get();

        $colors = [
            '#FF6B35',
            '#FF8C00',
            '#FFB347',
            '#FFA500',
            '#FF7F50',
            '#FF6347',
            '#FF4500',
            '#DC143C'
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (Rs)',
                    'data' => $serviceRevenue->pluck('total_revenue')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $serviceRevenue->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $serviceRevenue->pluck('category_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { 
                            var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                            var percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ": Rs " + context.parsed.toLocaleString() + " (" + percentage + "%)";
                        }',
                    ],
                ],
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
