<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Enums\UserStatus;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Actions\Action as FilamentAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                FileUpload::make('avatar')
                    ->label('Avatar')
                    ->image()
                    ->directory('avatars')
                    ->disk('public')
                    ->maxSize(2048),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->unique(User::class, 'email', ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->required()
                    ->regex('/^[0-9]{10,15}$/')
                    ->maxLength(15),
                Select::make('status')
                    ->options([
                        UserStatus::Pending->value => 'Pending',
                        UserStatus::Active->value => 'Active',
                        UserStatus::Inactive->value => 'Inactive',
                        UserStatus::Locked->value => 'Locked',
                    ])
                    ->required(),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->required(),
                DateTimePicker::make('last_login_at')
                    ->label('Last Login At')
                    ->disabled()
                    ->format('Y-m-d H:i:s'),
                TextInput::make('last_login_ip')
                    ->label('Last Login IP')
                    ->disabled(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->circular()
                    ->height(40)
                    ->width(40),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?UserStatus $state): string => $state ? ucfirst($state->value) : '')
                    ->color(fn (?UserStatus $state): string => match ($state) {
                        UserStatus::Pending => 'warning',
                        UserStatus::Active => 'success',
                        UserStatus::Inactive => 'danger',
                        UserStatus::Locked => 'secondary',
                        default => 'gray',
                    }),
                TextColumn::make('last_login_at')->dateTime('Y-m-d H:i'),
                TextColumn::make('created_at')->dateTime('Y-m-d'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        UserStatus::Pending->value => 'Pending',
                        UserStatus::Active->value => 'Active',
                        UserStatus::Inactive->value => 'Inactive',
                        UserStatus::Locked->value => 'Locked',
                    ]),
                SelectFilter::make('role')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\UserResource\Pages\ListUsers::route('/'),
            'create' => \App\Filament\Resources\UserResource\Pages\CreateUser::route('/create'),
            'edit' => \App\Filament\Resources\UserResource\Pages\EditUser::route('/{record}/edit'),
            'view' => \App\Filament\Resources\UserResource\Pages\ViewUser::route('/{record}'),
        ];
    }
}
