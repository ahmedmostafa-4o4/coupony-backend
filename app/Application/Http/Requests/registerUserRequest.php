<?php

namespace App\Application\Http\Requests;

use App\Domain\User\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                        return $fail(__('validation.custom.email.already_registered_seller_onboarding'));
                    else
                        return $fail(__('validation.custom.email.already_registered_verify'));
                }
                return $fail(__('validation.custom.email.already_registered'));
            }
        },
            ],
            'phone_number' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', 'string', Rule::in($this->allowedRoles())],
            'language' => ['nullable', 'string', Rule::in(array_keys(config('localization.supported_locales', [])))],
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedRoles(): array
    {
        if ($this->routeIs('admin.register')) {
            return ['admin'];
        }

        return ['customer', 'seller'];
    }

    /**
     * Custom attribute names for cleaner error messages
     */
    public function attributes(): array
    {
        return [
            'first_name' => __('validation.attributes.first_name'),
            'last_name' => __('validation.attributes.last_name'),
            'phone_number' => __('validation.attributes.phone_number'),
            'language' => __('validation.attributes.language'),
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.unique' => __('validation.custom.phone_number.unique'),
            'password.confirmed' => __('validation.custom.password.confirmed'),
            'role.in' => __('validation.custom.role.in'),
            'language.in' => __('validation.custom.language.in'),
        ];
    }


}
