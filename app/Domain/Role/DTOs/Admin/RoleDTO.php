<?php

namespace App\Domain\Role\DTOs\Admin;

use Illuminate\Http\Request;

readonly class RoleDTO
{
    public function __construct(
        public ?string $name,
        public ?array $permissions
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            permissions: $request->input('permissions')
        );
    }
}
