<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenaltyResource\Pages;
use App\Models\Invoice;
use App\Models\Penalty;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class PenaltyResource extends Resource
{
    protected static ?string $model = Penalty::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Financial Management';

    protected static ?int $navigationSort = 6;

    protected static ?string $pluralLabel = 'Travel Penalties';

    protected static ?string $label = 'Travel Penalty';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Penalty Information')
                    ->description('Enter the details of the penalty or fee')
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->label('Select Invoice')
                            ->relationship('invoice', 'invoice_number', function (Builder $query) {
                                return $query->whereNotIn('status', ['cancelled'])
                                    ->with(['customer', 'services'])
                                    ->orderBy('invoice_date', 'desc');
                            })
                            ->searchable(['invoice_number', 'customer.name', 'customer.email'])
                            ->getSearchResultsUsing(function (string $search): array {
                                return Invoice::with('customer')
                                    ->whereNotIn('status', ['cancelled'])
                                    ->where(function ($query) use ($search) {
                                        $query->where('invoice_number', 'like', "%{$search}%")
                                            ->orWhereHas('customer', function ($q) use ($search) {
                                                $q->where('name', 'like', "%{$search}%")
                                                    ->orWhere('email', 'like', "%{$search}%");
                                            });
                                    })
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(function ($invoice) {
                                        return [
                                            $invoice->id => $invoice->invoice_number . ' - ' . $invoice->customer->name . ' (Rs ' . number_format($invoice->total_amount, 2) . ')'
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $invoice = Invoice::with('customer')->find($value);
                                return $invoice ? $invoice->invoice_number . ' - ' . $invoice->customer->name : null;
                            })
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $invoice = Invoice::with('customer')->find($state);
                                    if ($invoice) {
                                        // Auto-fill tour date if available (handle visa-only invoices)
                                        if ($invoice->tour_date) {
                                            $set('original_tour_date', $invoice->tour_date->toDateString());
                                        } else {
                                            // For visa-only or other invoices without tour date
                                            $set('original_tour_date', null);
                                        }

                                        // Set penalty date to today
                                        $set('penalty_date', now()->toDateString());
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        // Invoice Preview Component
                        Forms\Components\Placeholder::make('invoice_preview')
                            ->label('')
                            ->content(function (Forms\Get $get): string {
                                $invoiceId = $get('invoice_id');
                                if (!$invoiceId) {
                                    return '';
                                }

                                $invoice = Invoice::with(['customer', 'services'])->find($invoiceId);
                                if (!$invoice) {
                                    return '';
                                }

                                $totalPaid = $invoice->payments()->sum('amount');
                                $effectiveAmount = $invoice->total_amount - ($invoice->total_refunded ?? 0) + ($invoice->total_penalties ?? 0);
                                $remainingBalance = $effectiveAmount - $totalPaid;

                                return view('filament.components.invoice-preview', [
                                    'invoice' => $invoice,
                                    'totalPaid' => $totalPaid,
                                    'remainingBalance' => $remainingBalance,
                                    'effectiveAmount' => $effectiveAmount
                                ])->render();
                            })
                            ->visible(fn(Forms\Get $get): bool => (bool) $get('invoice_id'))
                            ->columnSpanFull(),

                        Forms\Components\Select::make('penalty_type')
                            ->label('Penalty Type')
                            ->options([
                                'date_change' => 'Date Change Penalty',
                                'cancellation' => 'Cancellation Fee',
                                'late_booking' => 'Late Booking Fee',
                                'no_show' => 'No Show Penalty',
                                'amendment_fee' => 'Amendment Fee',
                                'supplier_penalty' => 'Supplier Penalty',
                                'other' => 'Other Penalty'
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Auto-set penalty date to today for new penalties
                                if ($state && !$set('penalty_date')) {
                                    $set('penalty_date', now()->toDateString());
                                }
                            }),

                        Forms\Components\TextInput::make('supplier_name')
                            ->label('Supplier/Vendor Name')
                            ->maxLength(255)
                            ->placeholder('e.g., Hotel ABC, Flight XYZ, Tour Operator'),
                    ])->columns(2),

                Section::make('Date Information')
                    ->description('Specify relevant dates for the penalty')
                    ->schema([
                        Forms\Components\DatePicker::make('original_tour_date')
                            ->label('Original Tour Date')
                            ->displayFormat('d/m/Y')
                            ->live(),

                        Forms\Components\DatePicker::make('new_tour_date')
                            ->label('New Tour Date')
                            ->displayFormat('d/m/Y')
                            ->visible(fn(Forms\Get $get) => $get('penalty_type') === 'date_change')
                            ->live(),

                        Forms\Components\DatePicker::make('penalty_date')
                            ->label('Penalty Date')
                            ->displayFormat('d/m/Y')
                            ->default(now())
                            ->required(),
                    ])->columns(3),

                Section::make('Financial Details')
                    ->description('Enter penalty amounts and cost allocation')
                    ->schema([
                        Forms\Components\TextInput::make('penalty_amount')
                            ->label('Total Penalty Amount')
                            ->numeric()
                            ->prefix('Rs ')
                            ->step(0.01)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $bearer = $get('penalty_bearer');
                                if ($state && $bearer) {
                                    // Auto-calculate amounts based on bearer
                                    switch ($bearer) {
                                        case 'customer':
                                            $set('customer_amount', $state);
                                            $set('agency_amount', 0);
                                            break;
                                        case 'agency':
                                            $set('customer_amount', 0);
                                            $set('agency_amount', $state);
                                            break;
                                        case 'shared':
                                            $half = round($state / 2, 2);
                                            $set('customer_amount', $half);
                                            $set('agency_amount', $state - $half);
                                            break;
                                    }
                                }
                            }),

                        Forms\Components\Select::make('penalty_bearer')
                            ->label('Who Bears the Penalty?')
                            ->options([
                                'customer' => 'Customer (Add to Invoice)',
                                'agency' => 'Agency (Absorb Cost)',
                                'shared' => 'Shared (Split Between Both)'
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $amount = $get('penalty_amount');
                                if ($amount && $state) {
                                    // Auto-calculate amounts based on bearer
                                    switch ($state) {
                                        case 'customer':
                                            $set('customer_amount', $amount);
                                            $set('agency_amount', 0);
                                            break;
                                        case 'agency':
                                            $set('customer_amount', 0);
                                            $set('agency_amount', $amount);
                                            break;
                                        case 'shared':
                                            $half = round($amount / 2, 2);
                                            $set('customer_amount', $half);
                                            $set('agency_amount', $amount - $half);
                                            break;
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('customer_amount')
                            ->label('Customer Amount (Add to Invoice)')
                            ->numeric()
                            ->prefix('Rs ')
                            ->step(0.01)
                            ->default(0),

                        Forms\Components\TextInput::make('agency_amount')
                            ->label('Agency Amount (Internal Cost)')
                            ->numeric()
                            ->prefix('Rs ')
                            ->step(0.01)
                            ->default(0),
                    ])->columns(2),

                Section::make('Invoice Re-issue Settings')
                    ->description('Configure how this penalty affects the invoice')
                    ->schema([
                        Forms\Components\Placeholder::make('reissue_warning')
                            ->content('⚠️ **Financial Accuracy Notice:** When an invoice is marked as re-issued, the original invoice will be automatically cancelled to prevent duplicate financial records and ensure accurate dashboard calculations.')
                            ->visible(fn(Forms\Get $get) => $get('penalty_type') === 'date_change')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('requires_invoice_reissue')
                            ->label('Requires Invoice Re-issue')
                            ->helperText('Enable this if the penalty requires updating tour dates or invoice details')
                            ->default(false)
                            ->live()
                            ->visible(fn(Forms\Get $get) => $get('penalty_type') === 'date_change'),

                        Forms\Components\Textarea::make('reissue_notes')
                            ->label('Re-issue Instructions')
                            ->placeholder('Specify what needs to be updated on the invoice (dates, services, etc.)')
                            ->rows(2)
                            ->visible(fn(Forms\Get $get) => $get('requires_invoice_reissue') && $get('penalty_type') === 'date_change'),

                        Forms\Components\Select::make('reissue_priority')
                            ->label('Re-issue Priority')
                            ->options([
                                'urgent' => 'Urgent (Process Today)',
                                'high' => 'High (Within 2 Days)',
                                'normal' => 'Normal (Within Week)',
                                'low' => 'Low (No Rush)'
                            ])
                            ->default('normal')
                            ->visible(fn(Forms\Get $get) => $get('requires_invoice_reissue') && $get('penalty_type') === 'date_change'),
                    ])->columns(1)
                    ->visible(fn(Forms\Get $get) => $get('penalty_type') === 'date_change'),

                Section::make('Additional Information')
                    ->description('Provide reason and supporting documentation')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Penalty Reason')
                            ->placeholder('Explain why this penalty was incurred...')
                            ->rows(2)
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Internal Notes')
                            ->placeholder('Additional notes for internal reference...')
                            ->rows(2),

                        Forms\Components\FileUpload::make('attachments')
                            ->label('Supporting Documents')
                            ->multiple()
                            ->directory('penalties')
                            ->acceptedFileTypes(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'])
                            ->maxSize(10240), // 10MB

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending Approval',
                                'approved' => 'Approved',
                                'applied' => 'Applied to Invoice',
                                'waived' => 'Waived',
                                'disputed' => 'Disputed'
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('penalty_type')
                    ->label('Type')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'date_change' => 'Date Change',
                        'cancellation' => 'Cancellation',
                        'late_booking' => 'Late Booking',
                        'no_show' => 'No Show',
                        'amendment_fee' => 'Amendment',
                        'supplier_penalty' => 'Supplier Penalty',
                        'other' => 'Other',
                        default => $state
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'date_change' => 'warning',
                        'cancellation' => 'danger',
                        'late_booking' => 'info',
                        'no_show' => 'danger',
                        'amendment_fee' => 'primary',
                        'supplier_penalty' => 'secondary',
                        'other' => 'gray',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('penalty_amount')
                    ->label('Total Amount')
                    ->money('LKR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('penalty_bearer')
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

                Tables\Columns\TextColumn::make('customer_amount')
                    ->label('Customer')
                    ->money('LKR')
                    ->color('success'),

                Tables\Columns\TextColumn::make('agency_amount')
                    ->label('Agency')
                    ->money('LKR')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('penalty_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'applied' => 'success',
                        'waived' => 'secondary',
                        'disputed' => 'danger',
                        default => 'gray'
                    }),

                Tables\Columns\IconColumn::make('requires_invoice_reissue')
                    ->label('Reissue Required')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-duplicate')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reissue_priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'gray'
                    })
                    ->visible(fn($record) => $record->requires_invoice_reissue)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('invoice_reissued')
                    ->label('Reissued')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->visible(fn($record) => $record->requires_invoice_reissue)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reissuedInvoice.invoice_number')
                    ->label('New Invoice')
                    ->url(fn($record) => $record->reissued_invoice_id ?
                        route('filament.admin.resources.invoices.view', $record->reissued_invoice_id) : null)
                    ->color('primary')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->tooltip('Click to view the re-issued invoice')
                    ->visible(fn($record) => $record->invoice_reissued && $record->reissued_invoice_id)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('penalty_type')
                    ->label('Penalty Type')
                    ->options([
                        'date_change' => 'Date Change',
                        'cancellation' => 'Cancellation',
                        'late_booking' => 'Late Booking',
                        'no_show' => 'No Show',
                        'amendment_fee' => 'Amendment',
                        'supplier_penalty' => 'Supplier Penalty',
                        'other' => 'Other'
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'applied' => 'Applied',
                        'waived' => 'Waived',
                        'disputed' => 'Disputed'
                    ]),

                SelectFilter::make('penalty_bearer')
                    ->label('Bearer')
                    ->options([
                        'customer' => 'Customer',
                        'agency' => 'Agency',
                        'shared' => 'Shared'
                    ]),

                Tables\Filters\Filter::make('penalty_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('penalty_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('penalty_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('requires_reissue')
                    ->label('Requires Invoice Re-issue')
                    ->query(fn(Builder $query): Builder => $query->where('requires_invoice_reissue', true))
                    ->toggle(),

                Tables\Filters\Filter::make('pending_reissue')
                    ->label('Pending Re-issue')
                    ->query(fn(Builder $query): Builder => $query->where('requires_invoice_reissue', true)
                        ->where('invoice_reissued', false)
                        ->where('status', 'applied'))
                    ->toggle(),

                SelectFilter::make('reissue_priority')
                    ->label('Re-issue Priority')
                    ->options([
                        'low' => 'Low Priority',
                        'medium' => 'Medium Priority',
                        'high' => 'High Priority',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value): Builder => $query->where('reissue_priority', $value)
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Penalty $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Penalty $record) {
                        if ($record->approve()) {
                            Notification::make()
                                ->title('Penalty Approved')
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('apply_to_invoice')
                    ->label('Apply to Invoice')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->color('primary')
                    ->visible(fn(Penalty $record): bool => $record->status === 'approved' && !$record->invoice_updated)
                    ->requiresConfirmation()
                    ->modalHeading('Apply Penalty to Invoice')
                    ->modalDescription(fn(Penalty $record) => "This will add Rs {$record->customer_amount} to Invoice #{$record->invoice->invoice_number}")
                    ->action(function (Penalty $record) {
                        if ($record->applyToInvoice()) {
                            Notification::make()
                                ->title('Penalty Applied to Invoice')
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('mark_reissued')
                    ->label('Mark Reissued')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('success')
                    ->visible(fn(Penalty $record): bool => $record->requires_invoice_reissue && !$record->invoice_reissued && $record->status === 'applied')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Invoice as Reissued')
                    ->modalDescription('Confirm that the invoice has been reissued with updated information.')
                    ->form([
                        Forms\Components\Textarea::make('reissue_completion_notes')
                            ->label('Completion Notes')
                            ->placeholder('Any additional notes about the reissue process...')
                            ->rows(3),

                        Forms\Components\Select::make('new_invoice_id')
                            ->label('New Re-issued Invoice')
                            ->placeholder('Select the newly created invoice')
                            ->searchable()
                            ->options(function () {
                                return \App\Models\Invoice::whereNotIn('status', ['cancelled', 'draft'])
                                    ->orderBy('created_at', 'desc')
                                    ->limit(50)
                                    ->pluck('invoice_number', 'id');
                            })
                            ->helperText('Select the new invoice that replaces the cancelled one')
                    ])
                    ->action(function (Penalty $record, array $data) {
                        if ($record->markInvoiceReissued($data['reissue_completion_notes'] ?? null, $data['new_invoice_id'] ?? null)) {
                            Notification::make()
                                ->title('Invoice Marked as Reissued')
                                ->body('Original invoice has been cancelled and new invoice linked.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Reissue Failed')
                                ->body('Failed to complete the invoice reissue process.')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('waive')
                    ->label('Waive')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn(Penalty $record): bool => in_array($record->status, ['pending', 'approved']))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('waive_reason')
                            ->label('Reason for Waiving')
                            ->required()
                            ->placeholder('Explain why this penalty is being waived...')
                    ])
                    ->action(function (Penalty $record, array $data) {
                        if ($record->waive($data['waive_reason'])) {
                            Notification::make()
                                ->title('Penalty Waived')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('penalty_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenalties::route('/'),
            'create' => Pages\CreatePenalty::route('/create'),
            'view' => Pages\ViewPenalty::route('/{record}'),
            'edit' => Pages\EditPenalty::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }
}
