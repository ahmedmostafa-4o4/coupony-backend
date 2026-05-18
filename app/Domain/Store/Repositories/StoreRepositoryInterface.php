<?php

namespace App\Domain\Store\Repositories;

use App\Domain\Store\Models\Store;

interface StoreRepositoryInterface
{
    public function create(array $data): Store;

    public function update(string $id, array $data): Store;

    public function delete(string $id): bool;

    public function find(string $id): ?Store;

    public function findByName(string $name): ?Store;

    public function all(): array;
}
