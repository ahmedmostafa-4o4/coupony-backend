<?php

namespace App\Domain\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Store\Models\StoreFollowers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guard_name = 'sanctum'; // <-- matches your role

    protected $table = 'users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'email',
        'password_hash',
        'phone_number',
        'email_verified_at',
        'phone_verified_at',
        'status',
        'last_login_at',
        'login_count',
        'shard_key',
        'remember_token',
        'two_factor_enabled',
        'last_ip',
        'provider',
        'provider_id',
        'language',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'login_count' => 'integer',
            'two_factor_enabled' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected $appends = [
        'full_name',
        'is_verified',
        'avatar',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Generate UUID if not set
            if (empty($user->id)) {
                $user->id = (string) \Illuminate\Support\Str::uuid();
            }

            // Generate shard key for partitioning
            if (empty($user->shard_key)) {
                $user->shard_key = substr(md5($user->email), 0, 8);
            }

            // Set default language and timezone
            $user->language = $user->language ?? 'ar';
            $user->timezone = $user->timezone ?? 'Africa/Cairo';
            $user->status = $user->status ?? 'active';
        });
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Set the password attribute.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = bcrypt($value);
    }

    /**
     * Accessor for password_hash as password
     */
    public function getPasswordAttribute()
    {
        return $this->password_hash;
    }

    public function getFullNameAttribute()
    {
        return $this->profile->first_name . ' ' . $this->profile->last_name;
    }

    public function getIsVerifiedAttribute()
    {
        return !is_null($this->email_verified_at);
    }

    public function getAvatarAttribute()
    {
        return $this->profile->avatar_url ?? null;
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function points()
    {
        return $this->hasOne(UserPoints::class);
    }

    public function stores()
    {
        return $this->hasMany(\App\Domain\Store\Models\Store::class, 'owner_user_id');
    }

    public function userRoles()
    {
        return $this->hasMany(UserRoles::class);
    }

    public function storeFollowers()
    {
        return $this->hasMany(StoreFollowers::class, 'user_id');
    }

    public function addresses()
    {
        return $this->morphToMany(Address::class, 'owner', 'addressables')
            ->withPivot([
                'label',
                'is_default_shipping',
                'is_default_billing',
            ])
            ->withTimestamps();
    }
}
