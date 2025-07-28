<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;




class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableEmptyStateHeading(): string
    {
        return 'No categories found';
    }

    protected function getTableEmptyStateDescription(): string
    {
        return 'You can create a new category by clicking the button below.';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
