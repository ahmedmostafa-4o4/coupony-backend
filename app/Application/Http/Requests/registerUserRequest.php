<?php

namespace App\Application\Http\Requests;

use App\Domain\User\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;

class registerUserRequest extends FormRequest
{
    public function __construct(private
        UserRepository $user
        )
    {
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
            $user = $this->user->findByEmail($value);
            if ($user) {
                if (($this->role ?? null) === 'seller') {
                    if ($user->email_verified_at !== null || $user->phone_verified_at !== null)
                        return $fail('This email is already registered. Please log in to continue seller onboarding.');
                    else
                        return $fail('This email is already registered. Please login to verify your email.');
                }
                return $fail('This email is already registered. Please log in.');
            }
        },
            ],
            'phone_number' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:customer,seller,admin',
        ];
    }

    /**
     * Custom attribute names for cleaner error messages
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'phone_number' => 'Phone Number',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.unique' => 'This phone number is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.in' => 'Invalid role selected.',
        ];
    }


}
