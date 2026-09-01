<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MicrosoftConnection extends Model
{
    protected $fillable = [
        'id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }
}
