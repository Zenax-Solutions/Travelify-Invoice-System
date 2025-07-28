<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Setting;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class InvoiceSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoice Settings';
    protected static ?string $navigationGroup = 'Invoice Management';
    protected static string $view = 'filament.pages.invoice-settings-page';
    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $this->loadSettingsData();
    }

    protected function loadSettingsData(): void
    {
        $settings = Setting::where('key', 'LIKE', 'invoice_%')->pluck('value', 'key')->toArray();

        // Decode JSON fields for contact numbers
        if (isset($settings['invoice_contact_numbers'])) {
            $contactNumbers = json_decode($settings['invoice_contact_numbers'], true);
            if (is_array($contactNumbers)) {
                // Convert array of strings to proper repeater format
                $settings['invoice_contact_numbers'] = array_map(function ($number) {
                    return ['number' => $number];
                }, $contactNumbers);
            } else {
                $settings['invoice_contact_numbers'] = [];
            }
        } else {
            $settings['invoice_contact_numbers'] = [
                ['number' => '(+94) 11 2 502 703'],
                ['number' => '(+94) 770 61 81 73'],
                ['number' => '(+94) 717 61 81 73']
            ];
        }

        // Decode JSON fields for payment accounts
        if (isset($settings['invoice_payment_accounts'])) {
            $paymentAccounts = json_decode($settings['invoice_payment_accounts'], true);
            $settings['invoice_payment_accounts'] = is_array($paymentAccounts) ? $paymentAccounts : [];
        } else {
            $settings['invoice_payment_accounts'] = [
                [
                    'name' => 'Travelify',
                    'bank' => 'Bank of Ceylon',
                    'account_number' => '93726343',
                    'branch' => 'Bambalapitiya',
                    'branch_code' => '775'
                ],
                [
                    'name' => 'Travelify',
                    'bank' => 'Nation Trust Bank',
                    'account_number' => '200250115619',
                    'branch' => 'Havelock City',
                    'branch_code' => '025'
                ]
            ];
        }

        // Ensure boolean fields are properly cast
        $booleanFields = [
            'invoice_logo_enabled',
            'invoice_show_contact_info',
            'invoice_show_logo_section',
            'invoice_show_customer_section',
            'invoice_show_contact_section',
            'invoice_show_invoice_details_section',
            'invoice_show_services_table',
            'invoice_show_payment_info',
            'invoice_show_terms_section',
            'invoice_show_footer_section',
            'invoice_email_enabled',
            'invoice_show_developer_credit'
        ];

        // Set default theme colors if not present
        $themeDefaults = [
            'invoice_primary_color' => '#FF6B35',
            'invoice_secondary_color' => '#FF8C00',
            'invoice_text_color' => '#333333',
            'invoice_background_color' => '#FFFFFF',
            'invoice_border_color' => '#DDDDDD',
            'invoice_header_bg_color' => '#FF6B35',
        ];

        foreach ($themeDefaults as $key => $defaultValue) {
            if (!isset($settings[$key])) {
                $settings[$key] = $defaultValue;
            }
        }

        foreach ($booleanFields as $field) {
            if (isset($settings[$field])) {
                $settings[$field] = (bool) ($settings[$field] === '1' || $settings[$field] === true);
            }
        }

        $this->data = $settings;
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Invoice Settings')
                    ->tabs([
                        Tabs\Tab::make('Company Info')
                            ->schema([
                                Section::make('Company Information')
                                    ->description('Configure your company details that appear on invoices')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('invoice_company_name')
                                                    ->label('Company Name')
                                                    ->required()
                                                    ->default('TRAVELIFY'),

                                                TextInput::make('invoice_company_tagline')
                                                    ->label('Company Tagline')
                                                    ->default('Travel Agency'),
                                            ]),

                                        Toggle::make('invoice_logo_enabled')
                                            ->label('Show Logo on Invoices'),
                                    ]),

                                Section::make('Contact Information')
                                    ->description('Configure contact details shown on invoices')
                                    ->schema([
                                        Toggle::make('invoice_show_contact_info')
                                            ->label('Show Contact Information')
                                            ->live(),

                                        Repeater::make('invoice_contact_numbers')
                                            ->label('Contact Numbers')
                                            ->schema([
                                                TextInput::make('number')
                                                    ->label('Phone Number')
                                                    ->required()
                                                    ->placeholder('e.g., (+94) 11 2 502 703')
                                            ])
                                            ->defaultItems(3)
                                            ->addActionLabel('Add Phone Number')
                                            ->visible(fn($get) => $get('invoice_show_contact_info'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Invoice Sections')
                            ->schema([
                                Section::make('Section Visibility')
                                    ->description('Control which sections appear on your invoices')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('invoice_show_logo_section')
                                                    ->label('Logo Section'),

                                                Toggle::make('invoice_show_customer_section')
                                                    ->label('Customer Information'),

                                                Toggle::make('invoice_show_contact_section')
                                                    ->label('Contact Information'),

                                                Toggle::make('invoice_show_invoice_details_section')
                                                    ->label('Invoice Details'),

                                                Toggle::make('invoice_show_services_table')
                                                    ->label('Services Table'),

                                                Toggle::make('invoice_show_payment_info')
                                                    ->label('Payment Information'),

                                                Toggle::make('invoice_show_terms_section')
                                                    ->label('Terms & Notes'),

                                                Toggle::make('invoice_show_footer_section')
                                                    ->label('Footer Section'),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Payment Info')
                            ->schema([
                                Section::make('Payment Accounts')
                                    ->description('Configure bank accounts for payment information')
                                    ->schema([
                                        Repeater::make('invoice_payment_accounts')
                                            ->label('Payment Accounts')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Account Name')
                                                            ->required(),

                                                        TextInput::make('bank')
                                                            ->label('Bank Name')
                                                            ->required(),
                                                    ]),

                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('account_number')
                                                            ->label('Account Number')
                                                            ->required(),

                                                        TextInput::make('branch')
                                                            ->label('Branch')
                                                            ->required(),
                                                    ]),

                                                TextInput::make('branch_code')
                                                    ->label('Branch Code')
                                                    ->required(),
                                            ])
                                            ->defaultItems(2)
                                            ->addActionLabel('Add Payment Account')
                                            ->collapsible(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Notes & Footer')
                            ->schema([
                                Section::make('Invoice Notes')
                                    ->description('Configure notes and messages that appear on invoices')
                                    ->schema([
                                        Textarea::make('invoice_footer_note')
                                            ->label('Footer Note')
                                            ->rows(2)
                                            ->placeholder('e.g., THIS IS A COMPUTER-GENERATED TAX INVOICE AND BEARS NO SIGNATURE'),

                                        TextInput::make('invoice_thank_you_message')
                                            ->label('Thank You Message')
                                            ->placeholder('e.g., Thank you for your trust and co-operation'),

                                        Textarea::make('invoice_additional_info')
                                            ->label('Additional Information')
                                            ->rows(3)
                                            ->placeholder('Any additional information to display on invoices'),
                                    ]),

                                Section::make('PDF & Footer Settings')
                                    ->description('Configure PDF-specific settings')
                                    ->schema([
                                        TextInput::make('invoice_pdf_footer')
                                            ->label('PDF Footer Text')
                                            ->placeholder('e.g., TRAVELIFY - Your trusted travel partner'),

                                        TextInput::make('invoice_developer_credit')
                                            ->label('Developer Credit')
                                            ->placeholder('e.g., Developed by ZENAX | www.zenax.info'),

                                        Toggle::make('invoice_show_developer_credit')
                                            ->label('Show Developer Credit'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Email Settings')
                            ->schema([
                                Section::make('Email Configuration')
                                    ->description('Configure email-specific invoice settings')
                                    ->schema([
                                        Toggle::make('invoice_email_enabled')
                                            ->label('Enable Email Invoices')
                                            ->live(),

                                        TextInput::make('invoice_email_subject')
                                            ->label('Email Subject')
                                            ->placeholder('Invoice #{invoice_number} from Travelify')
                                            ->helperText('Use {invoice_number} and {customer_name} as placeholders')
                                            ->visible(fn($get) => $get('invoice_email_enabled')),

                                        TextInput::make('invoice_email_greeting')
                                            ->label('Email Greeting')
                                            ->placeholder('Dear {customer_name},')
                                            ->helperText('Use {customer_name} as placeholder')
                                            ->visible(fn($get) => $get('invoice_email_enabled')),

                                        Textarea::make('invoice_email_message')
                                            ->label('Email Message')
                                            ->rows(3)
                                            ->placeholder('Thank you for choosing Travelify for your travel needs...')
                                            ->visible(fn($get) => $get('invoice_email_enabled')),
                                    ]),
                            ]),

                        Tabs\Tab::make('Theme & Colors')
                            ->schema([
                                Section::make('Invoice Theme')
                                    ->description('Customize the color scheme and appearance of your invoices')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                ColorPicker::make('invoice_primary_color')
                                                    ->label('Primary Color')
                                                    ->helperText('Main theme color (headers, buttons, accents)')
                                                    ->default('#FF6B35'),

                                                ColorPicker::make('invoice_secondary_color')
                                                    ->label('Secondary Color')
                                                    ->helperText('Secondary accents and highlights')
                                                    ->default('#FF8C00'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                ColorPicker::make('invoice_text_color')
                                                    ->label('Text Color')
                                                    ->helperText('Main text color')
                                                    ->default('#333333'),

                                                ColorPicker::make('invoice_background_color')
                                                    ->label('Background Color')
                                                    ->helperText('Invoice background color')
                                                    ->default('#FFFFFF'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                ColorPicker::make('invoice_border_color')
                                                    ->label('Border Color')
                                                    ->helperText('Table borders and dividers')
                                                    ->default('#DDDDDD'),

                                                ColorPicker::make('invoice_header_bg_color')
                                                    ->label('Table Header Background')
                                                    ->helperText('Background color for table headers')
                                                    ->default('#FF6B35'),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            // Encode JSON fields for contact numbers
            if (isset($data['invoice_contact_numbers']) && is_array($data['invoice_contact_numbers'])) {
                $contactNumbers = [];
                foreach ($data['invoice_contact_numbers'] as $item) {
                    if (is_array($item) && isset($item['number']) && !empty(trim($item['number']))) {
                        $contactNumbers[] = trim($item['number']);
                    }
                }
                $data['invoice_contact_numbers'] = json_encode($contactNumbers);
            }

            // Encode JSON fields for payment accounts
            if (isset($data['invoice_payment_accounts']) && is_array($data['invoice_payment_accounts'])) {
                // Clean up payment accounts - remove empty ones
                $paymentAccounts = [];
                foreach ($data['invoice_payment_accounts'] as $account) {
                    if (
                        is_array($account) &&
                        !empty(trim($account['name'] ?? '')) &&
                        !empty(trim($account['bank'] ?? ''))
                    ) {
                        $paymentAccounts[] = [
                            'name' => trim($account['name'] ?? ''),
                            'bank' => trim($account['bank'] ?? ''),
                            'account_number' => trim($account['account_number'] ?? ''),
                            'branch' => trim($account['branch'] ?? ''),
                            'branch_code' => trim($account['branch_code'] ?? ''),
                        ];
                    }
                }
                $data['invoice_payment_accounts'] = json_encode($paymentAccounts);
            }

            // Save each setting
            foreach ($data as $key => $value) {
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                } elseif (is_null($value)) {
                    $value = '';
                } elseif (is_array($value)) {
                    // Skip arrays that weren't properly encoded above
                    continue;
                }

                Setting::set($key, (string) $value);
            }

            Notification::make()
                ->title('Invoice settings saved successfully!')
                ->success()
                ->send();

            // Reload the settings data to refresh the form
            $this->loadSettingsData();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saving settings')
                ->body('An error occurred while saving: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->color('primary'),
        ];
    }
}
