<?php

namespace App\Filament\Resources;

use App\Filament\Exports\InvoiceExporter;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Filament\Resources\InvoiceResource\Widgets;
use App\Filament\Resources\InvoiceResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\InvoiceResource\Widgets\TotalInvoicesOverview;
use App\Filament\Resources\InvoiceResource\Widgets\TotalOutstandingAmountOverview;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ExportAction;
use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\RawJs;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Booking Confirmations';

    protected static ?string $navigationGroup = 'Booking Management';

    protected static ?int $navigationSort = 1;

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->invoice_number;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_number', 'customer.name'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('customer_id')
                            ->label('Customer')
                            ->options(fn() => Customer::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->label('Customer Name')
                                        ->placeholder('Enter customer full name')
                                        ->columnSpan(2)
                                        ->helperText('Enter the full name of the customer'),
                                    TextInput::make('email')
                                        ->email()
                                        ->maxLength(255)
                                        ->unique('customers', 'email')
                                        ->label('Email Address')
                                        ->placeholder('customer@example.com')
                                        ->helperText('Email for sending invoices'),
                                    TextInput::make('phone')
                                        ->tel()
                                        ->maxLength(255)
                                        ->label('Phone Number')
                                        ->placeholder('+1 (555) 123-4567')
                                        ->helperText('Contact phone number'),
                                    TextInput::make('address')
                                        ->maxLength(255)
                                        ->label('Address')
                                        ->placeholder('Enter full address')
                                        ->columnSpan(2)
                                        ->helperText('Complete postal address'),
                                ])
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $customer = Customer::create($data);

                                // Show success notification
                                Notification::make()
                                    ->title('Customer Created Successfully')
                                    ->body("New customer '{$customer->name}' has been created and selected.")
                                    ->success()
                                    ->duration(5000)
                                    ->send();

                                return $customer->id;
                            })
                            ->hint('Click "Create option" in the dropdown to add a new customer instantly')
                            ->hintIcon('heroicon-m-information-circle'),
                        TextInput::make('invoice_number')
                            ->formatStateUsing(function ($state) {

                                if (!empty($state)) {
                                    return $state; // If the state is already set, return it
                                }
                                $lastInvoice = Invoice::orderByDesc('id')->first();
                                $nextInvoiceNumber = $lastInvoice
                                    ? (int) substr($lastInvoice->invoice_number, 4) + 1
                                    : 1;

                                $state = 'INV-' . str_pad($nextInvoiceNumber, 6, '0', STR_PAD_LEFT);

                                return $state;
                            })
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        DatePicker::make('invoice_date')
                            ->required(),
                        DatePicker::make('due_date')
                            ->required(),
                        DatePicker::make('tour_date')
                            ->label('Tour Date')
                            ->nullable(),
                    ]),
                Repeater::make('services')
                    ->relationship('items')
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        // Auto-calculate total amount when services change
                        try {
                            $services = $get('services') ?? [];
                            $totalAmount = collect($services)->sum(function ($service) {
                                $quantity = (float) ($service['quantity'] ?? 0);
                                $unitPrice = (float) ($service['unit_price'] ?? 0);
                                return $quantity * $unitPrice;
                            });
                            $set('total_amount', $totalAmount);
                        } catch (\Exception $e) {
                            // Fallback to 0 if calculation fails
                            $set('total_amount', 0);
                        }
                    })
                    ->schema([
                        Select::make('service_id')
                            ->label('Service')
                            ->options(fn() => Service::with('category')->get()->mapWithKeys(function ($service) {
                                return [$service->id => $service->category ? "{$service->category->name} - {$service->name}" : $service->name];
                            }))
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->options(\App\Models\Category::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->helperText('Select or create a service category')
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Category Name')
                                            ->placeholder('e.g., Transportation, Accommodation')
                                            ->helperText('Enter a descriptive category name'),
                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        $category = \App\Models\Category::create($data);
                                        return $category->id;
                                    }),
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Service Name')
                                    ->placeholder('e.g., Airport Transfer, Hotel Booking')
                                    ->helperText('Enter a clear service name'),
                                TextInput::make('description')
                                    ->maxLength(500)
                                    ->label('Description')
                                    ->placeholder('Brief description of the service')
                                    ->helperText('Optional description for better identification'),
                                TextInput::make('price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rs')
                                    ->label('Default Price')
                                    ->placeholder('0.00')
                                    ->helperText('Default price for this service'),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $service = Service::create($data);

                                Notification::make()
                                    ->title('Service Created Successfully')
                                    ->body("New service '{$service->name}' has been created and selected.")
                                    ->success()
                                    ->duration(5000)
                                    ->send();

                                return $service->id;
                            })
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $service = Service::find($state);
                                if ($service) {
                                    $set('unit_price', $service->price);
                                    $quantity = (float) ($get('quantity') ?? 1);
                                    $unitPrice = (float) $service->price;
                                    $set('total', $quantity * $unitPrice);
                                }
                            })
                            ->hint('Click "Create option" to add a new service')
                            ->hintIcon('heroicon-m-information-circle'),
                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $quantity = (float) ($state ?? 1);
                                $unitPrice = (float) ($get('unit_price') ?? 0);
                                $set('total', $quantity * $unitPrice);
                            }),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->required()
                            ->prefix('Rs')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $unitPrice = (float) ($state ?? 0);
                                $quantity = (float) ($get('quantity') ?? 1);
                                $set('total', $unitPrice * $quantity);
                            }),
                        TextInput::make('total')
                            ->numeric()
                            ->formatStateUsing(function (Get $get, $state) {
                                if ($state) return $state;
                                $unitPrice = (float) ($get('unit_price') ?? 0);
                                $quantity = (float) ($get('quantity') ?? 1);
                                return $unitPrice * $quantity;
                            })
                            ->stripCharacters(',')
                            ->prefix('Rs')
                            ->readOnly(),
                    ])
                    ->columns(4)
                    ->addAction(function (Get $get, Set $set) {
                        // Auto-calculate total amount when services change
                        try {
                            $services = $get('services') ?? [];
                            $totalAmount = collect($services)->sum(function ($service) {
                                $quantity = (float) ($service['quantity'] ?? 0);
                                $unitPrice = (float) ($service['unit_price'] ?? 0);
                                return $quantity * $unitPrice;
                            });
                            $set('total_amount', $totalAmount);
                        } catch (\Exception $e) {
                            // Fallback to 0 if calculation fails
                            $set('total_amount', 0);
                        }
                    })
                    ->defaultItems(1)
                    ->createItemButtonLabel('Add Service')
                    ->columnSpan('full'),

                TextInput::make('total_amount')
                    ->prefix('Rs')
                    ->required()
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->searchable(),
                TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('tour_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('total_amount')
                    ->money('LKR')
                    ->sortable(),
                TextColumn::make('total_refunded')
                    ->label('Refunded')
                    ->money('LKR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(fn($state) => $state > 0 ? 'warning' : null),
                TextColumn::make('net_amount')
                    ->label('Net Amount')
                    ->money('LKR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn($record) => $record->total_refunded > 0 ? 'After refunds' : null),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        'partially_paid' => 'info',
                        'refunded' => 'purple',
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Primary Actions Group - Most frequently used
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->color('primary'),

                    TableAction::make('addPayment')
                        ->label('Add Payment')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->form([
                            Placeholder::make('payable_amount')
                                ->label('Payable Amount')
                                ->content(fn(Invoice $record): string => 'Rs' . number_format($record->remaining_balance, 2)),
                            TextInput::make('amount')
                                ->label('Payment Amount')
                                ->numeric()
                                ->required()
                                ->step(0.01)
                                ->maxValue(fn(Invoice $record): float => $record->remaining_balance),
                            DatePicker::make('payment_date')
                                ->label('Payment Date')
                                ->required()
                                ->default(now()),
                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'Cash' => 'Cash',
                                    'Bank Transfer' => 'Bank Transfer',
                                    'Card' => 'Card',
                                    'Other' => 'Other',
                                ])
                                ->nullable(),
                        ])
                        ->action(function (array $data, Invoice $record): void {
                            $record->payments()->create([
                                'amount' => $data['amount'],
                                'payment_date' => $data['payment_date'],
                                'payment_method' => $data['payment_method'],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Payment added successfully')
                                ->success()
                                ->send();
                        })
                        ->visible(fn(Invoice $record): bool => $record->remaining_balance > 0),

                    TableAction::make('viewInvoice')
                        ->label('View Invoice')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn(Invoice $record): string => route('invoices.show', $record))
                        ->openUrlInNewTab(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),

                // Document Actions Group - Print, Download, Email
                Tables\Actions\ActionGroup::make([
                    TableAction::make('downloadPDF')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->url(fn(Invoice $record): string => route('invoices.pdf', $record))
                        ->openUrlInNewTab(),

                    TableAction::make('sendEmail')
                        ->label('Send Email')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->action(function (Invoice $record) {
                            Mail::to($record->customer->email)->send(new InvoiceMail($record));
                            \Filament\Notifications\Notification::make()
                                ->title('Invoice sent')
                                ->success()
                                ->send();
                        })
                        ->visible(fn(Invoice $record): bool => !empty($record->customer->email)),
                ])
                    ->label('Documents')
                    ->icon('heroicon-m-document-text')
                    ->size('sm')
                    ->color('gray')
                    ->button(),

                // Financial Actions Group - Refunds and Cancellation
                Tables\Actions\ActionGroup::make([
                    TableAction::make('processRefund')
                        ->label('Process Refund')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->form([
                            Placeholder::make('available_refund')
                                ->label('Available for Refund')
                                ->content(fn(Invoice $record): string => 'Rs.' . number_format($record->available_refund_amount, 2)),
                            TextInput::make('refund_amount')
                                ->label('Refund Amount')
                                ->numeric()
                                ->required()
                                ->step(0.01)
                                ->maxValue(fn(Invoice $record): float => $record->available_refund_amount),
                            TextInput::make('refund_reason')
                                ->label('Refund Reason')
                                ->required()
                                ->maxLength(255),
                            Select::make('refund_method')
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
                            DatePicker::make('refund_date')
                                ->label('Refund Date')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function (array $data, Invoice $record): void {
                            try {
                                $refund = $record->processRefund(
                                    $data['refund_amount'],
                                    $data['refund_reason'],
                                    $data['refund_method'],
                                    Auth::id()
                                );

                                \Filament\Notifications\Notification::make()
                                    ->title('Refund processed successfully')
                                    ->body("Refund #{$refund->refund_number} for $" . number_format($data['refund_amount'], 2))
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Refund failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn(Invoice $record): bool => $record->isRefundable()),

                    TableAction::make('cancelInvoice')
                        ->label('Cancel Invoice')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Invoice')
                        ->modalDescription('Are you sure you want to cancel this invoice? This action cannot be undone.')
                        ->form([
                            TextInput::make('cancellation_reason')
                                ->label('Cancellation Reason')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Please provide a reason for cancelling this invoice...'),
                        ])
                        ->action(function (array $data, Invoice $record): void {
                            $cancelled = $record->cancel($data['cancellation_reason'], Auth::id());

                            if ($cancelled) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Invoice cancelled successfully')
                                    ->body("Invoice #{$record->invoice_number} has been cancelled")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Cannot cancel invoice')
                                    ->body('This invoice is already cancelled or cannot be cancelled')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn(Invoice $record): bool => !$record->isCancelled() && $record->status !== 'draft'),
                ])
                    ->label('Financial')
                    ->icon('heroicon-m-banknotes')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                // Primary Bulk Actions Group - Common operations
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Invoices')
                        ->modalDescription('Are you sure you want to delete these invoices? This action cannot be undone and will affect financial records.'),

                    // Enhanced Email Bulk Action
                    BulkAction::make('sendBulkEmail')
                        ->label('Send Email to All')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $sent = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                if (!empty($record->customer->email)) {
                                    try {
                                        Mail::to($record->customer->email)->send(new InvoiceMail($record));
                                        $sent++;
                                    } catch (\Exception $e) {
                                        $failed++;
                                    }
                                } else {
                                    $failed++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Bulk Email Status')
                                ->body("Sent: {$sent}, Failed: {$failed}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Send Email to Selected Invoices')
                        ->modalDescription('This will send invoice emails to all selected customers who have email addresses.'),
                ])
                    ->label('Bulk Actions')
                    ->color('primary'),

                // Status Management Bulk Actions
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('markAsPaid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->status !== 'paid') {
                                    $record->update(['status' => 'paid']);
                                    $updated++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Status Updated')
                                ->body("{$updated} invoices marked as paid")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Mark Selected Invoices as Paid')
                        ->modalDescription('This will update the status of selected invoices to "Paid".'),

                    BulkAction::make('markAsOverdue')
                        ->label('Mark as Overdue')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->status !== 'overdue') {
                                    $record->update(['status' => 'overdue']);
                                    $updated++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Status Updated')
                                ->body("{$updated} invoices marked as overdue")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Mark Selected Invoices as Overdue')
                        ->modalDescription('This will update the status of selected invoices to "Overdue".'),
                ])
                    ->label('Status Updates')
                    ->color('warning'),

                // Document Generation Bulk Actions
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('downloadBulkPDF')
                        ->label('Generate PDF Reports')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            \Filament\Notifications\Notification::make()
                                ->title('PDF Generation')
                                ->body("Preparing {$count} invoice PDFs for download...")
                                ->info()
                                ->send();

                            // In a real implementation, this would trigger background job
                            // For now, we'll just notify about the feature
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Generate PDF Reports')
                        ->modalDescription('This will prepare PDF reports for all selected invoices.'),
                ])
                    ->label('Reports')
                    ->color('info'),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
            RelationManagers\RefundsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            TotalInvoicesOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
