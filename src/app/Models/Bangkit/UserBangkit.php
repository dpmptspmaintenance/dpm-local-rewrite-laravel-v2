<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Akun internal bangkit (legacy `sekre_bangkit_user`). Kolom `isaktif` tanpa underscore — verbatim. */
class UserBangkit extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sekre_bangkit_user';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'role',
        'bidang',
        'id_pegawai',
        'isaktif',
        'created_by',
        'modified_by',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'isaktif' => 'boolean',
            'created_at' => 'datetime',
            'modified_at' => 'datetime',
        ];
    }

    /** Label role, dari legacy Auth.php redirect map. */
    public const ROLES = [
        1 => 'Superadmin',
        2 => 'Kadin',
        3 => 'Sekdin',
        4 => 'Kasubag',
        5 => 'Bendahara',
        6 => 'User',
    ];
}
