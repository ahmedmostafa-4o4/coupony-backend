<?php

namespace App\Domain\User\Models;

use App\Domain\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'company',
        'address_line1',
        'address_line2',
        'city',
        'state_province',
        'postal_code',
        'country_code',
        'phone_number',
        'latitude',
        'longitude',
        'delivery_instructions',
    ];

    public function users()
    {
        return $this->morphedByMany(User::class, 'owner', 'addressables')
            ->withPivot(['label', 'is_default_shipping', 'is_default_billing'])
            ->withTimestamps();
    }

    public function stores()
    {
        return $this->morphedByMany(Store::class, 'owner', 'addressables')
            ->withPivot(['label', 'is_default_shipping', 'is_default_billing'])
            ->withTimestamps();
    }
}