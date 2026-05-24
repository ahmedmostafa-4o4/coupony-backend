<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Domain\Subscription\Actions\ProcessWebhookAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WebhookController extends Controller
{
    public function __construct(
        private readonly ProcessWebhookAction $processWebhookAction,
    ) {}

    /**
     * Handle Paymob webhook callback.
     * No authentication middleware — HMAC-verified internally.
     */
    public function paymob(Request $request): JsonResponse
    {
        $payload = $request->all();
        $hmacSignature = $request->query('hmac', $request->header('X-Hmac', ''));

        try {
            $this->processWebhookAction->execute($payload, $hmacSignature);

            return response()->json(['success' => true], 200);
        } catch (HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
