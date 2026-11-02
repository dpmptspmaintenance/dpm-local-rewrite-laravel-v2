<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Models\ApiKey;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Kelola kunci API untuk integrasi eksternal (Chrome extension Generator
 * Surat Cuti). Kunci plaintext hanya ditampilkan sekali saat dibuat.
 *
 * Dokumentasi endpoint untuk pembuat extension ada di tab Dokumentasi
 * halaman ini (lihat view).
 */
class ApiKeys extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'API Key';

    protected static string|\UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'API Key';

    protected static ?string $slug = 'api-key';

    protected string $view = 'filament.kepegawaian.pages.api-keys';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user !== null && ((int) $user->role === 1 || (bool) $user->is_admin_kepegawaian);
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Kunci akses API untuk Chrome extension. Kunci memiliki scope generator-cuti saja.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ApiKey::query()->orderByDesc('id'))
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('medium'),
                TextColumn::make('key_prefix')->label('Prefix')->fontFamily('mono'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (ApiKey $record): string => $record->statusLabel())
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Kadaluarsa' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('user_nama')->label('Pembuat')->placeholder('—'),
                TextColumn::make('last_used_at')->label('Terakhir dipakai')->dateTime('d M Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('expires_at')->label('Kadaluarsa')->date('d M Y')->placeholder('—'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->sortable()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                Action::make('buat')
                    ->label('Buat Kunci Baru')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama kunci')
                            ->helperText('Mis. "Chrome Extension E-Cuti".')
                            ->required()
                            ->maxLength(100),
                        CheckboxList::make('scopes')
                            ->label('Scope')
                            ->options(ApiKey::SCOPES)
                            ->default([ApiKey::SCOPE_GENERATOR_CUTI])
                            ->required()
                            ->minItems(1),
                        DatePicker::make('expires_at')
                            ->label('Kadaluarsa (opsional)')
                            ->helperText('Kosong = tidak pernah kadaluarsa.')
                            ->native(false)
                            ->minDate(now()->format('Y-m-d')),
                    ])
                    ->action(function (array $data): void {
                        [$key, $plaintext] = ApiKey::terbitkan(
                            (string) $data['name'],
                            array_values($data['scopes'] ?? []),
                            Auth::user(),
                            filled($data['expires_at'] ?? null) ? now()->parse($data['expires_at'])->endOfDay() : null,
                        );

                        $this->dispatch('api-key-dibuat', kunci: $plaintext, nama: $key->name);

                        Notification::make()
                            ->title('Kunci dibuat: '.$key->key_prefix.'…')
                            ->body('Salin kunci di bawah SEKARANG — tidak bisa dilihat lagi.')
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('cabut')
                    ->label('Cabut')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cabut kunci API?')
                    ->modalDescription('Extension yang memakai kunci ini langsung berhenti berfungsi.')
                    ->visible(fn (ApiKey $record): bool => $record->revoked_at === null)
                    ->action(function (ApiKey $record): void {
                        $record->update(['revoked_at' => now()]);

                        Notification::make()->title('Kunci dicabut')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Belum ada kunci API');
    }
}
