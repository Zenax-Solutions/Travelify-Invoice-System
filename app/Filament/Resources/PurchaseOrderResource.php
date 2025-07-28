<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use App\Models\Service;
use App\Models\Setting;
use App\Mail\PurchaseOrderMail;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Tour Packages';

    protected static ?string $navigationGroup = 'Package Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $vendor = \App\Models\Vendor::find($state);
                        if ($vendor && !$vendor->is_service_provider) {
                            $set('items', [
                                [
                                    'service_id' => null,
                                    'description' => null,
                                    'quantity' => 1,
                                    'unit_price' => 0,
                                    'total' => 0,
                                ]
                            ]);
                        }
                    })
                    ->helperText(function (Get $get) {
                        if (!$get('vendor_id')) return null;

                        $vendor = \App\Models\Vendor::find($get('vendor_id'));
                        if (!$vendor || !$vendor->credit_limit) return null;

                        $creditUsage = $vendor->credit_usage_percentage;
                        $availableCredit = $vendor->available_credit;

                        $color = $creditUsage >= 90 ? 'text-red-600' : ($creditUsage >= 75 ? 'text-yellow-600' : 'text-green-600');

                        return new \Illuminate\Support\HtmlString(
                            "<div class='text-sm {$color}'>" .
                                "Credit Usage: {$creditUsage}% | Available Credit: Rs" . number_format($availableCredit, 2) .
                                ($creditUsage >= 90 ? " ⚠️ <strong>Credit Limit Nearly Reached!</strong>" : "") .
                                "</div>"
                        );
                    }),

                // Credit Limit Information Section
                Forms\Components\Section::make('Tour Supplier Credit Information')
                    ->schema([
                        Forms\Components\Placeholder::make('credit_info')
                            ->label('')
                            ->content(function (Get $get) {
                                if (!$get('vendor_id')) {
                                    return 'Select a tour supplier to view credit information.';
                                }

                                $vendor = \App\Models\Vendor::find($get('vendor_id'));
                                if (!$vendor) {
                                    return 'Vendor not found.';
                                }

                                if (!$vendor->credit_limit) {
                                    return 'This tour supplier does not have a credit limit set.';
                                }

                                $creditUsage = $vendor->credit_usage_percentage;
                                $outstandingBalance = $vendor->outstanding_balance;
                                $availableCredit = $vendor->available_credit;
                                $creditLimit = $vendor->credit_limit;

                                $statusColor = $creditUsage >= 90 ? 'red' : ($creditUsage >= 75 ? 'orange' : 'green');
                                $statusIcon = $creditUsage >= 90 ? '🔴' : ($creditUsage >= 75 ? '🟡' : '🟢');

                                return new \Illuminate\Support\HtmlString("
                                    <div class='space-y-2 p-4 bg-gray-50 rounded-lg'>
                                        <div class='flex items-center gap-2'>
                                            <span class='text-lg'>{$statusIcon}</span>
                                            <span class='font-semibold'>Credit Status: {$creditUsage}% Used</span>
                                        </div>
                                        <div class='grid grid-cols-2 gap-4 text-sm'>
                                            <div>
                                                <strong>Credit Limit:</strong> Rs" . number_format($creditLimit, 2) . "
                                            </div>
                                            <div>
                                                <strong>Outstanding Balance:</strong> Rs" . number_format($outstandingBalance, 2) . "
                                            </div>
                                            <div>
                                                <strong>Available Credit:</strong> Rs" . number_format($availableCredit, 2) . "
                                            </div>
                                            <div>
                                                <strong>Credit Usage:</strong> {$creditUsage}%
                                            </div>
                                        </div>
                                        " . ($creditUsage >= 90 ? "
                                        <div class='mt-3 p-2 bg-red-100 border border-red-300 rounded text-red-800'>
                                            <strong>⚠️ WARNING:</strong> Credit limit nearly reached! Consider contacting the supplier before placing large orders.
                                        </div>
                                        " : "") . "
                                    </div>
                                ");
                            })
                    ])
                    ->visible(fn(Get $get) => !empty($get('vendor_id')))
                    ->collapsible()
                    ->collapsed(false),
                Forms\Components\TextInput::make('po_number')
                    ->formatStateUsing(function ($state) {
                        if (!empty($state)) {
                            return $state;
                        }
                        $lastPo = \App\Models\PurchaseOrder::orderByDesc('id')->first();
                        $nextPoNumber = $lastPo
                            ? (int) substr($lastPo->po_number, 3) + 1
                            : 1;
                        return 'PO-' . str_pad($nextPoNumber, 6, '0', STR_PAD_LEFT);
                    })
                    ->required()
                    ->readOnly()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\DatePicker::make('po_date')
                    ->required(),
                Forms\Components\Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->relationship('service', 'name')
                            ->label('Service (Optional)')
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $service = \App\Models\Service::find($state);
                                if ($service) {
                                    $set('description', $service->name);
                                    $set('unit_price', $service->price);
                                    $quantity = (float) ($get('quantity') ?: 1);
                                    $set('total', $service->price * $quantity);

                                    // Trigger total recalculation for the entire form
                                    $items = $get('../../items') ?: [];
                                    $totalAmount = 0;
                                    foreach ($items as $item) {
                                        $totalAmount += (float) ($item['total'] ?? 0);
                                    }
                                    $set('../../total_amount', $totalAmount);
                                }
                            })
                            ->searchable()
                            ->hidden(fn(Get $get) => \App\Models\Vendor::find($get('../../vendor_id'))?->is_service_provider === false),
                        Forms\Components\TextInput::make('description')
                            ->required(fn(Get $get) => \App\Models\Vendor::find($get('../../vendor_id'))?->is_service_provider === false)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $quantity = (float) ($state ?: 0);
                                $unitPrice = (float) ($get('unit_price') ?: 0);
                                $set('total', $quantity * $unitPrice);

                                // Trigger total recalculation for the entire form
                                $items = $get('../../items') ?: [];
                                $totalAmount = 0;
                                foreach ($items as $item) {
                                    $totalAmount += (float) ($item['total'] ?? 0);
                                }
                                $set('../../total_amount', $totalAmount);
                            }),
                        Forms\Components\TextInput::make('unit_price')
                            ->numeric()
                            ->required()
                            ->prefix('Rs')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $unitPrice = (float) ($state ?: 0);
                                $quantity = (float) ($get('quantity') ?: 0);
                                $set('total', $unitPrice * $quantity);

                                // Trigger total recalculation for the entire form
                                $items = $get('../../items') ?: [];
                                $totalAmount = 0;
                                foreach ($items as $item) {
                                    $totalAmount += (float) ($item['total'] ?? 0);
                                }
                                $set('../../total_amount', $totalAmount);
                            }),
                        Forms\Components\TextInput::make('total')
                            ->numeric()
                            ->prefix('Rs')
                            ->readOnly(),
                    ])
                    ->columns(5)
                    ->addAction(function (Get $get, Set $set) {
                        static::calculateTotalAmount($get, $set);
                    })
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        static::calculateTotalAmount($get, $set);
                    })
                    ->defaultItems(1)
                    ->createItemButtonLabel('Add Item')
                    ->columnSpan('full'),
                Forms\Components\TextInput::make('total_amount')
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->readOnly()
                    ->helperText(function (Get $get) {
                        if (!$get('vendor_id') || !$get('total_amount')) return null;

                        $vendor = \App\Models\Vendor::find($get('vendor_id'));
                        if (!$vendor || !$vendor->credit_limit) return null;

                        $totalAmount = (float) $get('total_amount');

                        if ($vendor->wouldExceedCreditLimit($totalAmount)) {
                            $overage = $vendor->getCreditLimitOverage($totalAmount);
                            return new \Illuminate\Support\HtmlString(
                                "<div class='text-red-600 font-semibold'>" .
                                    "⚠️ <strong>CREDIT LIMIT EXCEEDED!</strong><br>" .
                                    "This order will exceed credit limit by Rs" . number_format($overage, 2) .
                                    "</div>"
                            );
                        } else {
                            $newBalance = $vendor->outstanding_balance + $totalAmount;
                            $usagePercentage = ($newBalance / $vendor->credit_limit) * 100;

                            if ($usagePercentage >= 90) {
                                return new \Illuminate\Support\HtmlString(
                                    "<div class='text-yellow-600 font-semibold'>" .
                                        "⚠️ <strong>WARNING:</strong> This order will use " .
                                        number_format($usagePercentage, 1) . "% of credit limit" .
                                        "</div>"
                                );
                            }

                            return new \Illuminate\Support\HtmlString(
                                "<div class='text-green-600'>" .
                                    "✓ Credit usage after this order: " .
                                    number_format($usagePercentage, 1) . "%" .
                                    "</div>"
                            );
                        }
                    })
                    ->rules([
                        function (Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                if (!$get('vendor_id') || !$value) return;

                                $vendor = \App\Models\Vendor::find($get('vendor_id'));
                                if (!$vendor || !$vendor->credit_limit) return;

                                if ($vendor->wouldExceedCreditLimit((float) $value)) {
                                    $overage = $vendor->getCreditLimitOverage((float) $value);
                                    $fail("This order exceeds the vendor's credit limit by Rs" . number_format($overage, 2) . ". Please reduce the order amount or contact the vendor to increase credit limit.");
                                }
                            };
                        }
                    ]),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->searchable()
                    ->sortable()
                    ->description(function ($record) {
                        $vendor = $record->vendor;
                        if (!$vendor || !$vendor->credit_limit) return null;

                        $usage = $vendor->credit_usage_percentage;
                        $status = $usage >= 90 ? '🔴 High' : ($usage >= 75 ? '🟡 Medium' : '🟢 Low');
                        return "Credit Risk: {$status} ({$usage}%)";
                    }),
                Tables\Columns\TextColumn::make('po_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('LKR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.credit_impact')
                    ->label('Credit Impact')
                    ->getStateUsing(function ($record) {
                        $vendor = $record->vendor;
                        if (!$vendor || !$vendor->credit_limit) return 'N/A';

                        $impactPercentage = ($record->total_amount / $vendor->credit_limit) * 100;

                        return number_format($impactPercentage, 1) . '%';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $vendor = $record->vendor;
                        if (!$vendor || !$vendor->credit_limit) return 'gray';

                        $impactPercentage = ($record->total_amount / $vendor->credit_limit) * 100;

                        return $impactPercentage >= 25 ? 'danger' : ($impactPercentage >= 15 ? 'warning' : 'success');
                    })
                    ->tooltip(function ($record) {
                        $vendor = $record->vendor;
                        if (!$vendor || !$vendor->credit_limit) return 'Vendor has no credit limit';

                        return "This order uses Rs" . number_format($record->total_amount, 2) . " of Rs" . number_format($vendor->credit_limit, 2) . " credit limit";
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'partially_paid' => 'info',
                        'cancelled' => 'danger',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // Print Action
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->url(fn(PurchaseOrder $record): string => route('purchase-orders.print', $record))
                    ->openUrlInNewTab(),

                // Download PDF Action
                Tables\Actions\Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (PurchaseOrder $record) {
                        $settings = Setting::getMany([
                            'invoice_company_name',
                            'invoice_company_tagline',
                            'invoice_logo_enabled',
                            'invoice_show_contact_info',
                            'invoice_contact_numbers',
                            'invoice_show_logo_section',
                            'invoice_show_customer_section',
                            'invoice_show_contact_section',
                            'invoice_show_invoice_details_section',
                            'invoice_show_services_table',
                            'invoice_show_payment_info',
                            'invoice_show_terms_section',
                            'invoice_show_footer_section',
                            'invoice_payment_accounts',
                            'invoice_footer_note',
                            'invoice_thank_you_message',
                            'invoice_additional_info',
                            'invoice_primary_color',
                            'invoice_secondary_color',
                            'invoice_text_color',
                            'invoice_background_color',
                            'invoice_border_color',
                            'invoice_header_bg_color',
                        ]);

                        $pdf = Pdf::loadView('purchase-orders.pdf', [
                            'purchaseOrder' => $record,
                            'settings' => $settings,
                        ])->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn() => print($pdf->output()),
                            'purchase-order-' . $record->po_number . '.pdf'
                        );
                    }),

                // Email Action
                Tables\Actions\Action::make('email')
                    ->label('Send Email')
                    ->icon('heroicon-o-envelope')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->default(fn(PurchaseOrder $record): string => $record->vendor->email ?? ''),
                        Forms\Components\Textarea::make('message')
                            ->label('Additional Message')
                            ->placeholder('Add any additional message for the vendor...')
                            ->rows(3),
                    ])
                    ->action(function (array $data, PurchaseOrder $record): void {
                        try {
                            Mail::to($data['email'])->send(new PurchaseOrderMail($record));

                            \Filament\Notifications\Notification::make()
                                ->title('Purchase order emailed successfully')
                                ->body('Purchase order #' . $record->po_number . ' has been sent to ' . $data['email'])
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Email failed')
                                ->body('Failed to send email: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('addPayment')
                    ->label('Add Payment')
                    ->icon('heroicon-o-currency-dollar')
                    ->form([
                        Forms\Components\Placeholder::make('payable_amount')
                            ->label('Payable Amount')
                            ->content(fn(\App\Models\PurchaseOrder $record): string => 'Rs' . number_format($record->remaining_balance, 2)),
                        Forms\Components\TextInput::make('amount')
                            ->label('Payment Amount')
                            ->numeric()
                            ->required()
                            ->step(0.01)
                            ->maxValue(fn(\App\Models\PurchaseOrder $record): float => $record->remaining_balance),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'Cash' => 'Cash',
                                'Bank Transfer' => 'Bank Transfer',
                                'Card' => 'Card',
                                'Other' => 'Other',
                            ])
                            ->nullable(),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpan('full'),
                    ])
                    ->action(function (array $data, \App\Models\PurchaseOrder $record): void {
                        $record->payments()->create([
                            'amount' => $data['amount'],
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'notes' => $data['notes'],
                        ]);

                        $record->updateTotalPaidAndStatus();

                        \Filament\Notifications\Notification::make()
                            ->title('Payment added successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(\App\Models\PurchaseOrder $record): bool => $record->remaining_balance > 0), // Hide if fully paid
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    // Bulk Email Action
                    Tables\Actions\BulkAction::make('bulkEmail')
                        ->label('Send Emails')
                        ->icon('heroicon-o-envelope')
                        ->form([
                            Forms\Components\Textarea::make('message')
                                ->label('Additional Message')
                                ->placeholder('Add any additional message for all vendors...')
                                ->rows(3),
                        ])
                        ->action(function (array $data, $records): void {
                            $successCount = 0;
                            $errorCount = 0;

                            foreach ($records as $record) {
                                if ($record->vendor->email) {
                                    try {
                                        Mail::to($record->vendor->email)->send(new PurchaseOrderMail($record));
                                        $successCount++;
                                    } catch (\Exception $e) {
                                        $errorCount++;
                                    }
                                } else {
                                    $errorCount++;
                                }
                            }

                            if ($successCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Bulk email completed')
                                    ->body("Successfully sent {$successCount} emails. {$errorCount} failed.")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Bulk email failed')
                                    ->body('No emails were sent successfully.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    protected static function calculateTotalAmount(Get $get, Set $set): void
    {
        $items = $get('items') ?: [];
        $totalAmount = 0;

        foreach ($items as $item) {
            $total = (float) ($item['total'] ?? 0);
            $totalAmount += $total;
        }

        $set('total_amount', $totalAmount);
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
