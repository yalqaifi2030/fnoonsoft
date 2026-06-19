<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpLocation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ip_address', 'country', 'country_name', 'region',
        'city', 'isp', 'is_proxy', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_proxy' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }
}
