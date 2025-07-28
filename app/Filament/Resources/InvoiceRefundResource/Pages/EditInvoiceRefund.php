<?php

namespace App\Filament\Resources\InvoiceRefundResource\Pages;

use App\Filament\Resources\InvoiceRefundResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceRefund extends EditRecord
{
    protected static string $resource = InvoiceRefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
