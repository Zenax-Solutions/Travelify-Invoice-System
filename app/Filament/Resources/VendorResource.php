<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Filament\Resources\VendorResource\RelationManagers;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Tour Suppliers';

    protected static ?string $navigationGroup = 'Package Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_person')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->columnSpan('full'),
                Forms\Components\TextInput::make('credit_limit')
                    ->label('Credit Limit')
                    ->numeric()
                    ->prefix('Rs')
                    ->step(0.01)
                    ->placeholder('Enter credit limit in LKR'),
                Forms\Components\Toggle::make('is_service_provider')
                    ->label('Service Provider')
                    ->helperText('Check if this vendor provides services (e.g., tours, air tickets).'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Credit Limit')
                    ->money('LKR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('outstanding_balance')
                    ->label('Outstanding Balance')
                    ->getStateUsing(fn($record) => $record->outstanding_balance)
                    ->money('LKR')
                    ->color(function ($record) {
                        if (!$record->credit_limit) return 'gray';
                        $usage = $record->credit_usage_percentage;
                        return $usage >= 90 ? 'danger' : ($usage >= 75 ? 'warning' : 'success');
                    })
                    ->sortable(),
                Tables\Columns\ViewColumn::make('credit_usage')
                    ->view('filament.columns.credit-progress-bar')
                    ->label('Credit Usage')
                    ->sortable(false),
                Tables\Columns\IconColumn::make('is_service_provider')
                    ->boolean()
                    ->label('Service Provider'),
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}
