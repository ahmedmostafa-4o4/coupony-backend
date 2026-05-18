<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\StoreCategoryResource;
use App\Domain\Store\Models\StoreCategory;
use Illuminate\Http\Request;

class UserStoreCategoryController extends Controller
{
    public function index(Request $request)
    {
        $this->applyAuthenticatedLocale($request);

        $categories = StoreCategory::active()->get();

        return $this->localizedJson([
            'data' => StoreCategoryResource::collection($categories),
        ]);
    }
}
