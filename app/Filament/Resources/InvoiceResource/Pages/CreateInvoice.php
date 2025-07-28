<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }



    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $data['status'] = 'pending';


        if ($data['due_date'] < $data['invoice_date']) {
            $data['due_date'] = $data['invoice_date'];
        }

        return $data;
    }
}
