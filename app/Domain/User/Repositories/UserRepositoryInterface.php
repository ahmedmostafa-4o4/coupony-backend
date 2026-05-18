<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Models\User;

interface UserRepositoryInterface
{
    public function create(array $data): User;

    public function update(string $id, array $data): User;

    public function delete(string $id): bool;

    public function find(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function all(): array;
}
