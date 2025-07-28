<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceRefundResource\Pages;
use App\Models\InvoiceRefund;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;

class InvoiceRefundResource extends Resource
{
    protected static ?string $model = InvoiceRefund::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Invoice Refunds';

    protected static ?string $modelLabel = 'Invoice Refund';

    protected static ?string $pluralModelLabel = 'Invoice Refunds';

    protected static ?string $navigationGroup = 'Financial Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Refund Information')
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->label('Invoice')
                            ->options(function () {
                                return \App\Models\Invoice::where('status', '!=', 'cancelled')
                                    ->get()
                                    ->pluck('invoice_number', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $invoice = Invoice::find($state);
                                    if ($invoice) {
                                        $set('max_refund_amount', $invoice->available_refund_amount);
                                    }
                                }
                            }),

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
                            ->prefix('Rs'),

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
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(1000),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('refund_number')
                    ->label('Refund #')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),

                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('Amount')
                    ->money('lkr')
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
                    ->label('Refund Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('processedBy.name')
                    ->label('Processed By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('System'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processed' => 'Processed',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('refund_method')
                    ->options([
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'credit_card' => 'Credit Card',
                        'check' => 'Check',
                        'other' => 'Other',
                    ]),

                Tables\Filters\Filter::make('refund_date')
                    ->form([
                        Forms\Components\DatePicker::make('refund_from')
                            ->label('Refund From'),
                        Forms\Components\DatePicker::make('refund_until')
                            ->label('Refund Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['refund_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('refund_date', '>=', $date),
                            )
                            ->when(
                                $data['refund_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('refund_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoiceRefunds::route('/'),
            'create' => Pages\CreateInvoiceRefund::route('/create'),
            'edit' => Pages\EditInvoiceRefund::route('/{record}/edit'),
        ];
    }
}
