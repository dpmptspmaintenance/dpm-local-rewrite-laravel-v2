<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'nama',
    'status_jabatan',
    'nip',
    'pangkat',
    'role',
    'bidang',
    'is_aktif',
    'google_id',
    'email',
    'name',
    'avatar',
    'password',
    'shared_pages',
    'is_bpp',
    'is_admin_persediaan',
    'is_admin_kepegawaian',
    'is_admin_arsip',
    'is_admin_bangkit',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function getFilamentName(): string
    {
        return (string) ($this->nama ?: ($this->name ?: ($this->email ?: 'User')));
    }

    /**
     * Gate per Filament panel. Kepegawaian: role 1 = full admin OR the
     * dedicated is_admin_kepegawaian flag, mirroring
     * ChecksPersediaanAccess::isAdmin(). User Management (`user` panel):
     * role 1 ONLY — is_admin_kepegawaian must not unlock system-wide user
     * administration, that flag is scoped to the HR module.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'user' => (int) $this->role === 1,
            // Arsip Digital: setiap pengguna aplikasi (yang sudah login lewat
            // Google OAuth) boleh masuk untuk mengunggah dokumen. Verifikasi
            // (approve/reject) dan pengelolaan kategori/tag dibatasi lebih
            // ketat lewat isArsipAdmin() di masing-masing Resource/Page, bukan
            // di gerbang panel ini.
            'arsip' => true,
            default => (int) $this->role === 1 || (bool) $this->is_admin_kepegawaian,
        };
    }

    /**
     * Verifikator/Admin Arsip: boleh membuka Dashboard Verifikasi, mengelola
     * kategori/tag, dan mengubah status dokumen siapa pun. Staf biasa cuma
     * bisa melihat/mengunggah dokumen miliknya sendiri (lihat
     * DocumentResource::canEdit()/scoping query di ListDocuments).
     */
    public function isArsipAdmin(): bool
    {
        return (int) $this->role === 1 || (bool) $this->is_admin_arsip;
    }

    /**
     * Admin modul Bangkit: role 1 atau flag is_admin_bangkit. Dipakai
     * ChecksBangkitAccess — legacy memperlakukan role 1 (superadmin) sebagai
     * admin penuh; flag tambahan biar bisa didelegasikan tanpa role global.
     */
    public function isBangkitAdmin(): bool
    {
        return (int) $this->role === 1 || (bool) $this->is_admin_bangkit;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'shared_pages' => 'array',
            'is_aktif' => 'boolean',
        ];
    }

    public function accessPages()
    {
        return $this->belongsToMany(AccessPage::class, 'access_page_user');
    }

    /**
     * Relasi ke master Ownership modul Arsip Digital.
     */
    public function ownerships(): BelongsToMany
    {
        return $this->belongsToMany(Ownership::class, 'ownership_user');
    }

    /**
     * Cek apakah user memiliki hak akses ke ownership tertentu.
     * Admin Arsip dan Superadmin (role 1) selalu memiliki akses penuh ke seluruh ownership.
     */
    public function canAccessOwnership(?int $ownershipId): bool
    {
        if ($this->isArsipAdmin()) {
            return true;
        }

        if ($ownershipId === null) {
            return true; // Dokumen umum / tanpa ownership khusus
        }

        return $this->ownerships()->where('ownerships.id', $ownershipId)->exists();
    }

    /**
     * Cek apakah user berhak mengakses dokumen arsip tertentu berdasarkan ownership.
     */
    public function canAccessDocument(Document $document): bool
    {
        if ($this->isArsipAdmin()) {
            return true;
        }

        // Pengunggah dokumen selalu boleh melihat dokumennya sendiri
        if ($document->created_by === $this->id) {
            return true;
        }

        // Dokumen orang lain hanya bisa dilihat jika sudah terbit (published) dan sesuai ownership
        if ($document->status !== Document::STATUS_PUBLISHED) {
            return false;
        }

        return $this->canAccessOwnership($document->ownership_id);
    }
}
