<?php

namespace App\Filament\Resources\PenaltyResource\Pages;

use App\Filament\Resources\PenaltyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Notifications\Notification;
use Filament\Forms;

class ViewPenalty extends ViewRecord
{
    protected static string $resource = PenaltyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

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
                    }
                }),

            Actions\Action::make('apply_to_invoice')
                ->label('Apply to Invoice')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('primary')
                ->visible(fn(): bool => $this->record->status === 'approved' && !$this->record->invoice_updated)
                ->requiresConfirmation()
                ->modalHeading('Apply Penalty to Invoice')
                ->modalDescription(fn() => "This will add ₹{$this->record->customer_amount} to Invoice #{$this->record->invoice->invoice_number}")
                ->action(function () {
                    if ($this->record->applyToInvoice()) {
                        Notification::make()
                            ->title('Penalty Applied to Invoice')
                            ->success()
                            ->send();
                    }
                }),

            Actions\Action::make('waive')
                ->label('Waive Penalty')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->visible(fn(): bool => in_array($this->record->status, ['pending', 'approved']))
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('waive_reason')
                        ->label('Reason for Waiving')
                        ->required()
                        ->placeholder('Explain why this penalty is being waived...')
                ])
                ->action(function (array $data) {
                    if ($this->record->waive($data['waive_reason'])) {
                        Notification::make()
                            ->title('Penalty Waived Successfully')
                            ->success()
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Split::make([
                    Section::make('Penalty Details')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('invoice.invoice_number')
                                        ->label('Invoice Number')
                                        ->badge()
                                        ->color('primary'),

                                    TextEntry::make('penalty_type')
                                        ->label('Penalty Type')
                                        ->formatStateUsing(fn(string $state): string => match ($state) {
                                            'date_change' => 'Date Change Penalty',
                                            'cancellation' => 'Cancellation Fee',
                                            'late_booking' => 'Late Booking Fee',
                                            'no_show' => 'No Show Penalty',
                                            'amendment_fee' => 'Amendment Fee',
                                            'supplier_penalty' => 'Supplier Penalty',
                                            'other' => 'Other Penalty',
                                            default => $state
                                        })
                                        ->badge()
                                        ->color('warning'),

                                    TextEntry::make('supplier_name')
                                        ->label('Supplier/Vendor'),

                                    TextEntry::make('status')
                                        ->badge()
                                        ->color(fn(string $state): string => match ($state) {
                                            'pending' => 'warning',
                                            'approved' => 'info',
                                            'applied' => 'success',
                                            'waived' => 'secondary',
                                            'disputed' => 'danger',
                                            default => 'gray'
                                        }),
                                ]),
                        ])->grow(),

                    Section::make('Quick Stats')
                        ->schema([
                            TextEntry::make('penalty_amount')
                                ->label('Total Amount')
                                ->money('INR')
                                ->size(TextEntry\TextEntrySize::Large),

                            TextEntry::make('penalty_bearer')
                                ->label('Bearer')
                                ->formatStateUsing(fn(string $state): string => match ($state) {
                                    'customer' => 'Customer',
                                    'agency' => 'Agency',
                                    'shared' => 'Shared',
                                    default => $state
                                })
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'customer' => 'success',
                                    'agency' => 'danger',
                                    'shared' => 'warning',
                                    default => 'gray'
                                }),

                            TextEntry::make('days_changed')
                                ->label('Days Changed')
                                ->visible(fn(): bool => $this->record->penalty_type === 'date_change' && $this->record->days_changed)
                                ->formatStateUsing(fn(?int $state): string => $state ? "{$state} days" : 'N/A'),
                        ])->grow(false),
                ]),

                Section::make('Date Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('original_tour_date')
                                    ->label('Original Tour Date')
                                    ->date('d M Y'),

                                TextEntry::make('new_tour_date')
                                    ->label('New Tour Date')
                                    ->date('d M Y')
                                    ->visible(fn(): bool => $this->record->penalty_type === 'date_change'),

                                TextEntry::make('penalty_date')
                                    ->label('Penalty Date')
                                    ->date('d M Y'),
                            ]),
                    ]),

                Section::make('Financial Breakdown')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('penalty_amount')
                                    ->label('Total Penalty Amount')
                                    ->money('INR')
                                    ->size(TextEntry\TextEntrySize::Large),

                                TextEntry::make('customer_amount')
                                    ->label('Customer Amount (Add to Invoice)')
                                    ->money('INR')
                                    ->color('success'),

                                TextEntry::make('agency_amount')
                                    ->label('Agency Amount (Internal Cost)')
                                    ->money('INR')
                                    ->color('danger'),
                            ]),
                    ]),

                Section::make('Details & Documentation')
                    ->schema([
                        TextEntry::make('reason')
                            ->label('Penalty Reason')
                            ->columnSpanFull(),

                        TextEntry::make('notes')
                            ->label('Internal Notes')
                            ->columnSpanFull()
                            ->visible(fn(): bool => !empty($this->record->notes)),

                        TextEntry::make('attachments')
                            ->label('Attachments')
                            ->listWithLineBreaks()
                            ->visible(fn(): bool => !empty($this->record->attachments)),
                    ]),

                Section::make('Approval Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('createdBy.name')
                                    ->label('Created By'),

                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('d M Y, H:i'),

                                TextEntry::make('approvedBy.name')
                                    ->label('Approved By')
                                    ->visible(fn(): bool => $this->record->approved_by),

                                TextEntry::make('approved_at')
                                    ->label('Approved At')
                                    ->dateTime('d M Y, H:i')
                                    ->visible(fn(): bool => $this->record->approved_at),
                            ]),
                    ]),

                Section::make('Integration Status')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('invoice_updated')
                                    ->label('Invoice Updated')
                                    ->formatStateUsing(fn(bool $state): string => $state ? 'Yes' : 'No')
                                    ->badge()
                                    ->color(fn(bool $state): string => $state ? 'success' : 'gray'),

                                TextEntry::make('expense_recorded')
                                    ->label('Expense Recorded')
                                    ->formatStateUsing(fn(bool $state): string => $state ? 'Yes' : 'No')
                                    ->badge()
                                    ->color(fn(bool $state): string => $state ? 'success' : 'gray'),
                            ]),
                    ]),
            ]);
    }
}
