<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * User Management — 1:1 port of the old Blade /user module (UserController +
 * views) onto Filament. Replaces: index/get_users (table+filters),
 * add_user (CreateAction), toggle_status (ToggleColumn on is_aktif),
 * add_gmail (per-row "Atur Gmail" Action, email-only update),
 * update_access/batch_update_access (per-row "Akses" Action + bulk action,
 * both writing shared_pages).
 *
 * shared_pages is not read anywhere else in the app for actual access
 * gating (checked during the port) — it only round-trips through this UI.
 * Kept as-is; not this port's job to wire it up to real gating.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static ?string $slug = 'pengguna';

    /**
     * Role labels, ported verbatim from UserController::ROLES.
     *
     * @var array<int, string>
     */
    public const ROLES = [
        1 => 'Superadmin',
        2 => 'Kadin',
        3 => 'Sekdin',
        4 => 'Kasubag',
        5 => 'Bendahara',
        6 => 'User',
        7 => 'Dinas Luar (App Local)',
        8 => 'Dinas Luar (App Khusus)',
    ];

    /**
     * Menu keys for the shared_pages access toggle, ported verbatim from
     * UserController::DAFTAR_MENU.
     *
     * @var array<string, string>
     */
    public const DAFTAR_MENU = [
        'rapat_kita' => 'Rapat Kita',
        'barang_kita' => 'Barang Kita',
        'data_kita' => 'Data Kita',
        'sikenut' => 'Sikenut',
        'botman' => 'Botman',
        'botman_manager' => 'Bot Manager',
        'siperdafit' => 'Siperdafit',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama Lengkap')
                ->required()
                ->maxLength(100),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('role')
                ->label('Role')
                ->options(self::ROLES)
                ->required(),
            TextInput::make('bidang')
                ->label('Bidang')
                ->maxLength(100),
            TextInput::make('status_jabatan')
                ->label('Status Jabatan')
                ->maxLength(100),
            TextInput::make('nip')
                ->label('NIP')
                ->maxLength(100),
            Toggle::make('is_aktif')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('Belum terhubung')
                    ->copyable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?int $state): string => self::ROLES[$state] ?? '—')
                    ->sortable(),
                TextColumn::make('bidang')
                    ->label('Bidang')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('shared_pages')
                    ->label('Akses Menu')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::DAFTAR_MENU[$state] ?? $state)
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->placeholder('—')
                    ->toggleable(),
                ToggleColumn::make('is_aktif')
                    ->label('Aktif'),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption('all')
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(self::ROLES),
                TernaryFilter::make('is_aktif')
                    ->label('Status Akun')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
                TernaryFilter::make('email_status')
                    ->label('Status Email')
                    ->placeholder('Semua')
                    ->trueLabel('Terhubung')
                    ->falseLabel('Belum Terhubung')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('email')->where('email', '!=', ''),
                        false: fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->whereNull('email')->orWhere('email', '')),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                Action::make('gmail')
                    ->label('Atur Gmail')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),
                    ])
                    ->fillForm(fn (User $record): array => ['email' => $record->email])
                    ->action(function (User $record, array $data): void {
                        $exists = User::where('email', $data['email'])->where('id', '!=', $record->id)->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Email sudah digunakan oleh pengguna lain')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['email' => $data['email']]);

                        Notification::make()
                            ->title('Email berhasil diperbarui')
                            ->success()
                            ->send();
                    }),
                Action::make('akses')
                    ->label('Akses')
                    ->icon('heroicon-o-key')
                    ->color('gray')
                    ->schema([
                        CheckboxList::make('pages')
                            ->label('Menu yang dapat diakses')
                            ->options(self::DAFTAR_MENU)
                            ->columns(2),
                    ])
                    ->fillForm(fn (User $record): array => ['pages' => $record->shared_pages ?? []])
                    ->action(function (User $record, array $data): void {
                        $record->update(['shared_pages' => array_values($data['pages'] ?? [])]);

                        Notification::make()
                            ->title('Akses menu diperbarui')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('batchAkses')
                        ->label('Batch Update Akses')
                        ->icon('heroicon-o-key')
                        ->schema([
                            CheckboxList::make('pages')
                                ->label('Menu yang dapat diakses')
                                ->options(self::DAFTAR_MENU)
                                ->columns(2),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $pages = array_values($data['pages'] ?? []);

                            foreach ($records as $record) {
                                $record->update(['shared_pages' => $pages]);
                            }

                            Notification::make()
                                ->title('Akses menu diperbarui untuk '.$records->count().' pengguna')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Tidak ada data pengguna');
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
