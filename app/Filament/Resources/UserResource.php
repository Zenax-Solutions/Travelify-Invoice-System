<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Staff Members';

    protected static ?string $navigationGroup = 'Staff Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Full Name'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignorable: fn($record) => $record)
                            ->label('Email Address'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn(string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            ->label('Password')
                            ->helperText('Leave blank to keep current password when editing'),
                    ])->columns(2),

                Forms\Components\Section::make('Role Assignment')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->options(function () {
                                return Role::all()->pluck('name', 'id')->toArray();
                            })
                            ->descriptions(function () {
                                $roles = Role::all();
                                $descriptions = [];

                                foreach ($roles as $role) {
                                    $descriptions[$role->id] = match ($role->name) {
                                        'super_admin' => 'Full system access - bypasses all permissions',
                                        'Travel Manager' => 'Travel agency manager - complete access to all modules',
                                        'Financial Controller' => 'Financial management - bookings, travelers, and financial data',
                                        'Travel Consultant' => 'Travel consultant - manage travelers and bookings',
                                        'Package Coordinator' => 'Package coordinator - manage suppliers and tour packages',
                                        'Manager' => 'Travel agency manager - complete access to all modules',
                                        'Accountant' => 'Financial management - bookings, travelers, and financial data',
                                        'Sales Representative' => 'Travel consultant - manage travelers and bookings',
                                        'Purchase Manager' => 'Package coordinator - manage suppliers and tour packages',
                                        'Viewer' => 'Read-only access to all travel data',
                                        default => 'Staff role with specific travel permissions'
                                    };
                                }

                                return $descriptions;
                            })
                            ->columns(2)
                            ->label('User Roles'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Full Name'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->label('Email Address'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'Manager',
                        'success' => 'Accountant',
                        'info' => 'Sales Representative',
                        'primary' => 'Purchase Manager',
                        'gray' => 'Viewer',
                    ])
                    ->label('Roles'),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Email Verified')
                    ->placeholder('Not verified')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Updated')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Role')
                    ->multiple(),
                Tables\Filters\Filter::make('verified')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->label('Email Verified Only'),
                Tables\Filters\Filter::make('unverified')
                    ->query(fn(Builder $query): Builder => $query->whereNull('email_verified_at'))
                    ->label('Unverified Email Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit User'),
                Tables\Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset User Password')
                    ->modalDescription('This will reset the user password to "password". The user should change it on next login.')
                    ->action(function (User $record) {
                        $record->update(['password' => Hash::make('password')]);

                        \Filament\Notifications\Notification::make()
                            ->title('Password Reset')
                            ->body('Password has been reset to "password"')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete User'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create First User'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
