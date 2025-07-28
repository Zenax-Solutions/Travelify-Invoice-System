<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\InvoiceRefund;

class RefundsRelationManager extends RelationManager
{
    protected static string $relationship = 'refunds';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Refund Details')
                    ->schema([
                        Forms\Components\TextInput::make('refund_number')
                            ->label('Refund Number')
                            ->default(fn() => 'REF-' . str_pad(InvoiceRefund::count() + 1, 6, '0', STR_PAD_LEFT))
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->prefix('$')
                            ->maxValue(fn($livewire) => $livewire->ownerRecord->available_refund_amount),

                        Forms\Components\Select::make('refund_method')
                            ->label('Refund Method')
                            ->options([
                                'bank_transfer' => 'Bank Transfer',
                                'cash' => 'Cash',
                                'credit_card' => 'Credit Card',
                                'check' => 'Check',
                                'other' => 'Other',
                            ])
                            ->default('bank_transfer')
                            ->required(),

                        Forms\Components\DatePicker::make('refund_date')
                            ->label('Refund Date')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'processed' => 'Processed',
                                'failed' => 'Failed',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('refund_reason')
                            ->label('Refund Reason')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(1000),
                    ])->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('refund_number')
            ->columns([
                Tables\Columns\TextColumn::make('refund_number')
                    ->label('Refund #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('refund_method')
                    ->label('Method')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'bank_transfer' => 'primary',
                        'cash' => 'success',
                        'credit_card' => 'warning',
                        'check' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('refund_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('refund_reason')
                    ->label('Reason')
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->refund_reason;
                    }),

                Tables\Columns\TextColumn::make('processedBy.name')
                    ->label('Processed By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processed' => 'Processed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['processed_by'] = Auth::id();
                        $data['processed_at'] = now();
                        return $data;
                    })
                    ->after(function ($record, $livewire): void {
                        $livewire->ownerRecord->updateRefundTotals();

                        \Filament\Notifications\Notification::make()
                            ->title('Refund created successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record, $livewire): void {
                        $livewire->ownerRecord->updateRefundTotals();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
