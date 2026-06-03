<?php

namespace App\Domain\User\DTOs\Admin;

use Illuminate\Http\Request;

readonly class UserFilterDTO
{
    public function __construct(
        public ?string $search,
        public ?string $role,
        public ?string $status,
        public ?string $fromDate,
        public ?string $toDate,
        public int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: $request->input('search') ?? $request->input('q'),
            role: $request->input('role'),
            status: $request->input('status'),
            fromDate: $request->input('from_date'),
            toDate: $request->input('to_date'),
            perPage: (int) ($request->input('per_page') ?? $request->input('perPage') ?? 15),
        );
    }
}
