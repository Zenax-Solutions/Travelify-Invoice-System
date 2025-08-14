<?php

namespace App\Filament\Resources\PenaltyResource\Pages;

use App\Filament\Resources\PenaltyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListPenalties extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = PenaltyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Penalty'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PenaltyResource\Widgets\PenaltyStatsWidget::class,
        ];
    }
}
