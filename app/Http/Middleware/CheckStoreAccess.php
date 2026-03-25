<?php

namespace App\Http\Middleware;

use App\Domain\Store\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission = null): Response
    {
        $store = $request->route('store');
        $user = $request->user();

        if (!$store instanceof Store) {
            $store = Store::findOrFail($store);
        }

        // Check if user has access
        if (!$store->hasUser($user)) {
            return response()->json([
                'message' => __('api.middleware.store_unauthorized'),
            ], 403);
        }

        // Check specific permission if required
        if ($permission === 'manage' && !$store->canBeManageBy($user)) {
            return response()->json([
                'message' => __('api.middleware.insufficient_permissions'),
            ], 403);
        }

        $request->merge(['store' => $store]);
        return $next($request);
    }
}
