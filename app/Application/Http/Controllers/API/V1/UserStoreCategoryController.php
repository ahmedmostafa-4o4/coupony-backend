<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Domain\Store\Models\StoreCategory;


class UserStoreCategoryController extends Controller
{
    public function index()
    {
        $categories = StoreCategory::active()->get();
        return response()->json($categories);
    }
}
