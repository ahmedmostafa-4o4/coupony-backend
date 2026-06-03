<?php

namespace App\Domain\User\DTOs\Admin;

use Illuminate\Http\Request;

readonly class StoreUserDTO
{
    public function __construct(
        public string $email,
        public ?string $phoneNumber,
        public string $password,
        public string $role,
        public string $firstName,
        public string $lastName,
        public ?string $language,
        public ?string $timezone,
        public ?string $status,
        public ?string $dateOfBirth,
        public ?string $gender,
        public ?string $bio,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            email: $request->input('email'),
            phoneNumber: $request->input('phone_number'),
            password: $request->input('password'),
            role: $request->input('role'),
            firstName: $request->input('first_name'),
            lastName: $request->input('last_name'),
            language: $request->input('language'),
            timezone: $request->input('timezone'),
            status: $request->input('status'),
            dateOfBirth: $request->input('date_of_birth'),
            gender: $request->input('gender'),
            bio: $request->input('bio'),
        );
    }
}
