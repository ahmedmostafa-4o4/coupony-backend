<?php

namespace App\Domain\Store\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreEmployee extends Model
{
    use HasFactory;

    protected $table = 'store_employees';

    protected $fillable = [
        'store_id',
        'user_id',
        'address_id',
        'role',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function address()
    {
        return $this->belongsTo(\App\Domain\User\Models\Address::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
