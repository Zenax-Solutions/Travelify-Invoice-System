<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Category;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return Category::all()
            ->mapWithKeys(function ($category) {
                return [
                    'all' => Tab::make(),
                    $category->id => Tab::make($category->name)->modifyQueryUsing(fn(Builder $query) => $query->where('category_id', $category->id)),
                ];
            })
            ->toArray();
    }
}
