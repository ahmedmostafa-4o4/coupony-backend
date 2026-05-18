<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminProducts
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->products->adminPaginate($filters, $perPage);
    }
}
