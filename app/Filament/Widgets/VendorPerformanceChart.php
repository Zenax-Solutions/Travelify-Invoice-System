<?php

namespace App\Filament\Widgets;

use App\Models\Vendor;
use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class VendorPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Top 10 Vendors by Purchase Volume';
    protected static ?string $description = 'Most active vendors this year';
    protected static ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $vendors = Vendor::select('vendors.name')
            ->join('purchase_orders', 'vendors.id', '=', 'purchase_orders.vendor_id')
            ->whereYear('purchase_orders.po_date', now()->year)
            ->groupBy('vendors.id', 'vendors.name')
            ->selectRaw('SUM(purchase_orders.total_amount) as total_purchases')
            ->orderByDesc('total_purchases')
            ->limit(10)
            ->get();

        $colors = [
            '#FF6B35',
            '#FF8C00',
            '#FFB347',
            '#FFA500',
            '#FF7F50',
            '#FF6347',
            '#FF4500',
            '#DC143C',
            '#B22222',
            '#8B0000'
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Purchase Amount (Rs)',
                    'data' => $vendors->pluck('total_purchases')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $vendors->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $vendors->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.label + ": Rs " + context.parsed.toLocaleString(); }',
                    ],
                ],
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
            ],
        ];
    }
}
