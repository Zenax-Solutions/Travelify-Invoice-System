<?php

namespace App\Filament\Resources\InvoiceRefundResource\Pages;

use App\Filament\Resources\InvoiceRefundResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceRefunds extends ListRecords
{
    protected static string $resource = InvoiceRefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
