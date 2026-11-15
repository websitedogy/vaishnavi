<?php

namespace App\Modules\SuperAdmin\Services;

use App\Modules\SuperAdmin\Models\Payment;
use App\Modules\SuperAdmin\Models\PaymentTransaction;
use App\Modules\SuperAdmin\Models\PaymentWebhookEvent;
use App\Modules\SuperAdmin\Models\Refund;
use App\Modules\SuperAdmin\Models\SubscriptionInvoice;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentGatewayService
{
    /**
     * Handles webhook payloads idempotently with signature validation.
     */
    public function handleWebhook(string $gateway, array $payload, ?string $signature = null): array
    {
        $eventId = $payload['event_id'] ?? $payload['id'] ?? ('wh_' . md5(json_encode($payload)));

        // Idempotency check: if event already processed, return immediately
        $existingEvent = PaymentWebhookEvent::where('event_id', $eventId)->first();
        if ($existingEvent && $existingEvent->is_processed) {
            return [
                'success' => true,
                'message' => 'Event already processed idempotently.',
                'event_id' => $eventId,
                'duplicate' => true,
            ];
        }

        $signatureValid = $this->verifySignature($gateway, $payload, $signature);

        $webhookRecord = PaymentWebhookEvent::updateOrCreate(
            ['event_id' => $eventId],
            [
                'gateway' => $gateway,
                'event_type' => $payload['event'] ?? 'payment.captured',
                'payload' => $payload,
                'signature_verified' => $signatureValid,
                'is_processed' => false,
            ]
        );

        if (!$signatureValid && !app()->environment('local', 'testing')) {
            $webhookRecord->update(['error' => 'Invalid webhook signature.']);
            throw new \InvalidArgumentException("Invalid {$gateway} webhook signature.");
        }

        // Process payment fulfillment
        return DB::transaction(function () use ($webhookRecord, $payload, $gateway) {
            $amount = floatval($payload['amount'] ?? 0);
            $tenantId = intval($payload['tenant_id'] ?? 0);
            $invoiceId = isset($payload['invoice_id']) ? intval($payload['invoice_id']) : null;
            $now = Carbon::now();

            $paymentRef = 'PAY-' . strtoupper(Str::random(12));

            $payment = Payment::create([
                'tenant_id' => $tenantId ?: (Tenant::first()->id ?? 1),
                'invoice_id' => $invoiceId,
                'transaction_reference' => $paymentRef,
                'gateway' => $gateway,
                'amount' => $amount,
                'tax' => $amount * 0.18,
                'fee' => 30.00,
                'currency' => $payload['currency'] ?? 'INR',
                'status' => 'success',
                'payment_method' => $payload['method'] ?? 'card',
                'metadata' => $payload,
            ]);

            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'gateway_transaction_id' => $payload['gateway_payment_id'] ?? Str::uuid(),
                'gateway_order_id' => $payload['gateway_order_id'] ?? null,
                'payload' => $payload,
                'response' => ['status' => 'captured', 'timestamp' => $now->toIso8601String()],
                'status' => 'success',
            ]);

            if ($invoiceId) {
                SubscriptionInvoice::where('id', $invoiceId)->update([
                    'status' => 'paid',
                    'paid_at' => $now,
                ]);
            }

            $webhookRecord->update([
                'is_processed' => true,
                'processed_at' => $now,
            ]);

            AuditHelper::log(null, 'PAYMENT_CAPTURED_WEBHOOK', 'Payment', $payment->id, "Payment {$paymentRef} of INR {$amount} processed via webhook.");

            return [
                'success' => true,
                'message' => 'Payment processed successfully.',
                'payment_id' => $payment->id,
                'reference' => $paymentRef,
            ];
        });
    }

    /**
     * Issues a safe refund with audit logs.
     */
    public function processRefund(int $paymentId, float $amount, string $reason, ?int $adminId = null): Refund
    {
        return DB::transaction(function () use ($paymentId, $amount, $reason, $adminId) {
            $payment = Payment::with('refunds')->findOrFail($paymentId);

            $alreadyRefunded = $payment->refunds->where('status', 'completed')->sum('amount');
            $remaining = $payment->amount - $alreadyRefunded;

            if ($amount > $remaining) {
                throw new \InvalidArgumentException("Refund amount ({$amount}) exceeds eligible remaining payment balance ({$remaining}).");
            }

            $refundRef = 'REF-' . strtoupper(Str::random(10));

            $refund = Refund::create([
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
                'refund_reference' => $refundRef,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'completed',
                'processed_by_admin_id' => $adminId,
            ]);

            if ($amount >= $remaining) {
                $payment->update(['status' => 'refunded']);
            }

            AuditHelper::log($adminId, 'PAYMENT_REFUNDED', 'Payment', $payment->id, "Refund of INR {$amount} processed for {$payment->transaction_reference}. Reason: {$reason}");

            return $refund;
        });
    }

    protected function verifySignature(string $gateway, array $payload, ?string $signature): bool
    {
        if (empty($signature)) {
            return app()->environment('local', 'testing');
        }

        $secret = config("services.{$gateway}.webhook_secret", 'mock_webhook_secret_dogy');
        $expected = hash_hmac('sha256', json_encode($payload), $secret);

        return hash_equals($expected, $signature);
    }
}
