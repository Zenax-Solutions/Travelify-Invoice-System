<?php

namespace App\Filament\Resources\PenaltyResource\Pages;

use App\Filament\Resources\PenaltyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPenalty extends EditRecord
{
    protected static string $resource = PenaltyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),

            Actions\Action::make('approve')
                ->label('Approve Penalty')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn(): bool => $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(function () {
                    if ($this->record->approve()) {
                        Notification::make()
                            ->title('Penalty Approved Successfully')
                            ->success()
                            ->send();

                        return redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    }
                }),

            Actions\Action::make('apply_to_invoice')
                ->label('Apply to Invoice')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('primary')
                ->visible(fn(): bool => $this->record->status === 'approved' && !$this->record->invoice_updated)
                ->requiresConfirmation()
                ->modalHeading('Apply Penalty to Invoice')
                ->modalDescription(fn() => "This will add Rs {$this->record->customer_amount} to Invoice #{$this->record->invoice->invoice_number}")
                ->action(function () {
                    if ($this->record->applyToInvoice()) {
                        Notification::make()
                            ->title('Penalty Applied to Invoice')
                            ->success()
                            ->send();

                        return redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    }
                }),
        ];
    }
}
