<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\Vendor;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $data = $this->form->getState();
        $originalAmount = $this->record->total_amount;

        // Calculate total amount from items if not set properly
        if (isset($data['items']) && (!isset($data['total_amount']) || $data['total_amount'] == 0)) {
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $totalAmount += (float) ($item['total'] ?? 0);
            }
            $this->form->fill(array_merge($data, ['total_amount' => $totalAmount]));
            $data = $this->form->getState();
        }

        if (isset($data['vendor_id']) && isset($data['total_amount'])) {
            $vendor = Vendor::find($data['vendor_id']);

            if ($vendor && $vendor->credit_limit) {
                // Calculate the difference in amount
                $amountDifference = (float) $data['total_amount'] - (float) $originalAmount;

                $newBalance = $vendor->outstanding_balance + $amountDifference;

                if ($newBalance > $vendor->credit_limit) {
                    $overage = $newBalance - $vendor->credit_limit;

                    Notification::make()
                        ->title('Credit Limit Exceeded!')
                        ->body("This update exceeds {$vendor->name}'s credit limit by Rs" . number_format($overage, 2) . ". Please reduce the order amount or contact the supplier to increase their credit limit.")
                        ->danger()
                        ->persistent()
                        ->send();

                    $this->halt();
                }

                $usagePercentage = ($newBalance / $vendor->credit_limit) * 100;

                if ($usagePercentage >= 90) {
                    Notification::make()
                        ->title('Credit Limit Warning')
                        ->body("This update will use " . number_format($usagePercentage, 1) . "% of {$vendor->name}'s credit limit. Please monitor future orders carefully.")
                        ->warning()
                        ->send();
                }
            }
        }
    }

    protected function afterSave(): void
    {
        // Ensure total amount is calculated correctly after saving
        $this->record->calculateTotalAmount();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Tour package order updated successfully';
    }
}
