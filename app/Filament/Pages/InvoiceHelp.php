<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class InvoiceHelp extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string $view = 'filament.pages.invoice-help';

    protected static ?string $navigationGroup = 'Help & Support';

    protected static ?string $title = 'Invoice Creation Guide';

    protected static ?int $navigationSort = 99;
}
