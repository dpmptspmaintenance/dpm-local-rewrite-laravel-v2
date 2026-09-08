<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
}
