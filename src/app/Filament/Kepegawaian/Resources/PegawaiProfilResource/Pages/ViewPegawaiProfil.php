<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Models\Kepegawaian\PegawaiProfil;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPegawaiProfil extends ViewRecord
{
    protected static string $resource = PegawaiProfilResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->nama ?? 'Detail Pegawai';
    }

    public function getSubheading(): ?string
    {
        return $this->getRecord()->nip;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Aktif/nonaktif TIDAK disimpan di pegawai_profil (tabel hasil
            // scrape, read-only). Status aktif melekat pada akun pengguna
            // (users.is_aktif) yang NIP-nya cocok dengan pegawai ini.
            Action::make('toggleAkun')
                ->label(fn (): string => $this->pegawai()->isAkunAktif() ? 'Nonaktifkan Akun' : 'Aktifkan Akun')
                ->icon(fn (): string => $this->pegawai()->isAkunAktif() ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                ->color(fn (): string => $this->pegawai()->isAkunAktif() ? 'danger' : 'success')
                ->visible(fn (): bool => $this->bolehKelolaAkun() && $this->pegawai()->akun() !== null)
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->pegawai()->isAkunAktif()
                    ? 'Nonaktifkan akun pegawai ini?'
                    : 'Aktifkan kembali akun pegawai ini?')
                ->modalDescription(fn (): string => $this->pegawai()->isAkunAktif()
                    ? 'Pegawai tidak bisa login lagi sampai akun diaktifkan kembali. Data kepegawaian tidak berubah.'
                    : 'Pegawai bisa login kembali memakai akun Google yang sama.')
                ->modalSubmitActionLabel(fn (): string => $this->pegawai()->isAkunAktif() ? 'Nonaktifkan' : 'Aktifkan')
                ->action(function (): void {
                    $this->toggleAkun();
                }),
        ];
    }

    private function pegawai(): PegawaiProfil
    {
        /** @var PegawaiProfil $record */
        $record = $this->getRecord();

        return $record;
    }

    /**
     * Hanya superadmin / admin kepegawaian yang boleh mengubah status akun.
     */
    private function bolehKelolaAkun(): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        return (int) $user->role === 1 || (bool) $user->is_admin_kepegawaian;
    }

    private function toggleAkun(): void
    {
        if (! $this->bolehKelolaAkun()) {
            Notification::make()->title('Tidak punya izin mengubah status akun.')->danger()->send();

            return;
        }

        $pegawai = $this->pegawai();
        $akun = $pegawai->akun();

        if ($akun === null) {
            Notification::make()
                ->title('Pegawai ini belum punya akun pengguna')
                ->body('NIP pegawai tidak cocok dengan akun mana pun di tabel pengguna.')
                ->warning()
                ->send();

            return;
        }

        if ((int) $akun->getKey() === (int) Auth::id()) {
            Notification::make()
                ->title('Tidak bisa menonaktifkan akun sendiri')
                ->body('Minta admin lain untuk mengubah status akun Anda.')
                ->danger()
                ->send();

            return;
        }

        $akun->forceFill(['is_aktif' => ! $akun->is_aktif])->save();

        // Cache keaktifan di-reset supaya badge & filter langsung ikut.
        PegawaiProfil::forgetAktifCache();

        $aktif = (bool) $akun->is_aktif;

        Notification::make()
            ->title($aktif ? 'Akun diaktifkan' : 'Akun dinonaktifkan')
            ->body($pegawai->nama.' ('.$pegawai->nip.')')
            ->success()
            ->send();

        // Muat ulang halaman supaya status akun di infolist ikut ter-update.
        $this->redirect(static::getUrl(['record' => $pegawai]));
    }
}
