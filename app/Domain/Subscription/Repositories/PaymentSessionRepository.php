<?php

namespace App\Domain\Subscription\Repositories;

use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Models\PaymentSession;

class PaymentSessionRepository
{
    /**
     * Find a pending (non-expired) session for a store.
     */
    public function findPendingByStore(string $storeId): ?PaymentSession
    {
        return PaymentSession::where('store_id', $storeId)
            ->pending()
            ->first();
    }

    /**
     * Find a payment session by its ID.
     */
    public function findBySessionId(string $sessionId): ?PaymentSession
    {
        return PaymentSession::find($sessionId);
    }

    /**
     * Mark a payment session as paid.
     */
    public function markAsPaid(PaymentSession $session, ?string $transactionId = null): PaymentSession
    {
        $session->update([
            'status' => PaymentSessionStatus::PAID,
            'paid_at' => now(),
            'paymob_transaction_id' => $transactionId ?? $session->paymob_transaction_id,
        ]);

        return $session->fresh();
    }

    /**
     * Mark a payment session as failed.
     */
    public function markAsFailed(PaymentSession $session, ?string $reason = null): PaymentSession
    {
        $session->update([
            'status' => PaymentSessionStatus::FAILED,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);

        return $session->fresh();
    }

    /**
     * Expire all pending sessions past their TTL.
     *
     * @return int The number of sessions expired.
     */
    public function expireSessions(): int
    {
        return PaymentSession::where('status', PaymentSessionStatus::PENDING)
            ->where('expires_at', '<=', now())
            ->update(['status' => PaymentSessionStatus::EXPIRED]);
    }
}
