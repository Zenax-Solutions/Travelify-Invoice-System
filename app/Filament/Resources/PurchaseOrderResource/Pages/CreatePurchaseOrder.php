<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\Vendor;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

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
                $totalAmount = (float) $data['total_amount'];

                if ($vendor->wouldExceedCreditLimit($totalAmount)) {
                    $overage = $vendor->getCreditLimitOverage($totalAmount);

                    Notification::make()
                        ->title('Credit Limit Exceeded!')
                        ->body("This tour package order exceeds {$vendor->name}'s credit limit by Rs" . number_format($overage, 2) . ". Please reduce the order amount or contact the supplier to increase their credit limit.")
                        ->danger()
                        ->persistent()
                        ->send();

                    $this->halt();
                }

                $newBalance = $vendor->outstanding_balance + $totalAmount;
                $usagePercentage = ($newBalance / $vendor->credit_limit) * 100;

                if ($usagePercentage >= 90) {
                    Notification::make()
                        ->title('Credit Limit Warning')
                        ->body("This order will use " . number_format($usagePercentage, 1) . "% of {$vendor->name}'s credit limit. Please monitor future orders carefully.")
                        ->warning()
                        ->send();
                }
            }
        }
    }

    protected function afterCreate(): void
    {
        // Ensure total amount is calculated correctly after creation
        $this->record->calculateTotalAmount();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Tour package order created successfully';
    }
}
