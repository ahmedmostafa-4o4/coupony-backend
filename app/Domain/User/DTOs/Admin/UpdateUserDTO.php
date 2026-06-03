<?php

namespace App\Domain\User\DTOs\Admin;

readonly class UpdateUserDTO
{
    public function __construct(
        public array $data
    ) {
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
    
    public function all(): array
    {
        return $this->data;
    }
}
