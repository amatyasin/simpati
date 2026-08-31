<?php

namespace App\Filament\Resources;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Pengguna')
                    ->icon('heroicon-o-user')
                    ->description('Detail profil dan kredensial pengguna sistem.')
                    ->schema([
                        FileUpload::make('avatar')
                            ->id('user_avatar')
                            ->extraInputAttributes(['id' => 'user_avatar'])
                            ->label('Foto / Avatar')
                            ->image()
                            ->directory('avatars')
                            ->disk('public')
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->id('user_name')
                            ->extraInputAttributes(['id' => 'user_name'])
                            ->label('Nama Lengkap')
                            ->placeholder('Contoh: Budi Santoso')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->id('user_email')
                            ->extraInputAttributes(['id' => 'user_email'])
                            ->label('Email')
                            ->placeholder('user@simpati.id')
                            ->email()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->required()
                            ->maxLength(255),

                        TextInput::make('password')
                            ->id('user_password')
                            ->extraInputAttributes(['id' => 'user_password'])
                            ->label('Password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(8)
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->id('user_phone')
                            ->extraInputAttributes(['id' => 'user_phone'])
                            ->label('Nomor Telepon')
                            ->placeholder('0812...')
                            ->maxLength(15),

                        Select::make('roles')
                            ->id('user_roles')
                            ->extraInputAttributes(['id' => 'user_roles'])
                            ->label('Peran / Role')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->required(),

                        Select::make('status')
                            ->id('user_status')
                            ->extraInputAttributes(['id' => 'user_status'])
                            ->label('Status Akun')
                            ->options([
                                UserStatus::Pending->value => 'Pending',
                                UserStatus::Active->value => 'Active',
                                UserStatus::Inactive->value => 'Inactive',
                                UserStatus::Locked->value => 'Locked',
                            ])
                            ->required(),
                    ])->columns(2),

                Section::make('Aktivitas Login & System')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        DateTimePicker::make('last_login_at')
                            ->id('last_login_at')
                            ->label('Terakhir Login')
                            ->disabled()
                            ->format('Y-m-d H:i:s'),

                        TextInput::make('last_login_ip')
                            ->id('last_login_ip')
                            ->extraInputAttributes(['id' => 'last_login_ip'])
                            ->label('IP Terakhir Login')
                            ->disabled(),

                        Toggle::make('is_active')
                            ->id('user_is_active')
                            ->label('Akun Aktif')
                            ->default(true)
                            ->columnSpanFull(),
                    ])->columns(2),
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
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Informasi Detail Pengguna')
                ->icon('heroicon-o-user')
                ->schema([
                    ImageEntry::make('avatar')
                        ->label('Foto / Avatar')
                        ->circular(),

                    TextEntry::make('name')
                        ->label('Nama Lengkap')
                        ->weight('bold'),

                    TextEntry::make('email')
                        ->label('Email'),

                    TextEntry::make('phone')
                        ->label('Nomor Telepon')
                        ->placeholder('—'),

                    TextEntry::make('roles.name')
                        ->label('Peran / Role')
                        ->badge()
                        ->color('primary')
                        ->placeholder('—'),

                    TextEntry::make('status')
                        ->label('Status Akun')
                        ->badge()
                        ->formatStateUsing(fn (?UserStatus $state): string => $state ? ucfirst($state->value) : '—')
                        ->color(fn (?UserStatus $state): string => match ($state) {
                            UserStatus::Pending => 'warning',
                            UserStatus::Active => 'success',
                            UserStatus::Inactive => 'danger',
                            UserStatus::Locked => 'secondary',
                            default => 'gray',
                        }),

                    TextEntry::make('last_login_at')
                        ->label('Terakhir Login')
                        ->dateTime('d M Y H:i:s')
                        ->placeholder('Belum Pernah Login'),

                    TextEntry::make('last_login_ip')
                        ->label('IP Terakhir')
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->label('Terdaftar Pada')
                        ->dateTime('d M Y H:i'),
                ])->columns(4),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
            'view' => ViewUser::route('/{record}'),
        ];
    }
}
