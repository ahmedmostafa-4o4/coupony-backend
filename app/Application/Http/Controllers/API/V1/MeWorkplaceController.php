<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeWorkplaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();

        $workplaces = $user->storeEmployeeAssignments()
            ->with('store:id,name,logo_url')
            ->get();

        $data = $workplaces->map(function ($employee) {
            return [
                'store_id' => $employee->store_id,
                'store_name' => $employee->store ? $employee->store->name : null,
                'logo_url' => $employee->store ? $employee->store->logo_url : null,
                'role' => $employee->role,
                'permissions' => $employee->permissions ?? [],
                'address_id' => $employee->address_id,
                'joined_at' => $employee->created_at?->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
