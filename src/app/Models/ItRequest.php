<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItRequest extends Model
{
    protected $table = 'it_requests';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'request_type',
        'status',
        'priority',
        'rejection_reason',
        'pending_reason',
        'approved_by',
        'accepted_by',
        'rejected_by',
        'created_at',
        'approved_at',
        'accepted_at',
        'rejected_at',
        'finish_time',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'created_at' => 'datetime',
            'approved_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'finish_time' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function accepter()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function files()
    {
        return $this->hasMany(ItRequestFile::class, 'request_id');
    }
}
