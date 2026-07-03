<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'avatar_url',
        'bio',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function (Profile $profile) {
            // Ensure that first and last names are capitalized
            $profile->first_name = ucfirst(strtolower($profile->first_name));
            $profile->last_name = ucfirst(strtolower($profile->last_name));

            $gender = strtolower((string) $profile->gender);
            $profile->gender = in_array($gender, ['male', 'female'], true) ? $gender : null;

            // Ensure that date of birth is a valid date
            if ($profile->date_of_birth && ! checkdate($profile->date_of_birth->month, $profile->date_of_birth->day, $profile->date_of_birth->year)) {
                $profile->date_of_birth = null;
            }

            // Ensure that avatar URL is a valid URL
            $profile->avatar_url ??= config('app.url').'/users/avatars/default.svg';
        });
    }

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user's age.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth
            ? $this->date_of_birth->age
            : null;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ProfileFactory::new();
    }
}
