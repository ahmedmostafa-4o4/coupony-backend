<?php

namespace App\Domain\User\DTOs;

use App\Application\Http\Requests\registerUserRequest;

class UserData
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $phone_number,
        public readonly ?string $password,
        public readonly string $role,
        public readonly ?string $provider = null,
        public readonly ?string $providerId = null,
        public readonly string $language = 'ar'
    ) {}

    public static function fromRequest(registerUserRequest $request): self
    {
        return new self(
            firstName: $request->input('first_name'),
            lastName: $request->input('last_name'),
            email: $request->input('email'),
            phone_number: $request->input('phone_number'),
            password: $request->input('password'),
            role: $request->input('role'),
            provider: $request->input('provider'),
            providerId: $request->input('provider_id'),
            language: $request->input('language', app()->getLocale()),
        );
    }

    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'phone_number' => $this->phone_number,
            'provider' => $this->provider,
            'provider_id' => $this->providerId,
            'language' => $this->language,
        ];
    }
}
