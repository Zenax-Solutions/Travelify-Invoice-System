<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class FutureToursChart extends ChartWidget
{
    protected static ?string $heading = 'Future Tours Overview';

    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $data = Invoice::query()
            ->selectRaw('DATE(tour_date) as tour_date, count(*) as count')
            ->where('tour_date', '>', now())
            ->groupBy('tour_date')
            ->orderBy('tour_date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Number of Tours',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => '#eb7e36ff',
                    'borderColor' => 'transparent',
                    'barThickness' => 40,

                ],
            ],
            'labels' => $data->pluck('tour_date')->map(fn($date) => Carbon::parse($date)->format('M d, Y'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
