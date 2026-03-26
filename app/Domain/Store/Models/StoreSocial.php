<?php

namespace App\Domain\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSocial extends Model
{
    /**
     * Create a new class instance.
     */
    /** @use HasFactory<\Database\Factories\StoreSocialFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'store_id',
        'social_id',
        'link',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function social()
    {
        return $this->belongsTo(Social::class);
    }
}
