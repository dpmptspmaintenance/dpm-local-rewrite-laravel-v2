<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItRequestFile extends Model
{
    protected $table = 'it_request_files';

    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'file_name',
        'file_path',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(ItRequest::class, 'request_id');
    }
}
