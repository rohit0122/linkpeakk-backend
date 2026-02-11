<?php

namespace App\Services;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    protected RazorpayService $razorpayService;

    protected PaymentService $paymentService;

    public function __construct(
        RazorpayService $razorpayService,
        PaymentService $paymentService
    ) {
        $this->razorpayService = $razorpayService;
        $this->paymentService = $paymentService;
    }

    public function handle(Request $request)
    {
        $rawPayload = $request->getContent();
        $signature = $request->headers->get('x-razorpay-signature');
        $secret = config('services.razorpay.webhook_secret');

        // ---------- SIGNATURE ----------
        if (! $signature) {
            if (app()->environment('local')) {
                Log::warning('Razorpay Webhook: Signature skipped (local env)');
            } else {
                Log::error('Razorpay Webhook: Signature header missing');

                return response()->json(['error' => 'Signature missing'], 400);
            }
        }

        if (! app()->environment('local')) {
            if (! $this->razorpayService->verifyWebhookSignature($rawPayload, $signature, $secret)) {
                Log::warning('Razorpay Webhook: Invalid signature');

                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        // ---------- PAYLOAD ----------
        $payload = json_decode($rawPayload, true);

        if (! is_array($payload)) {
            Log::error('Razorpay Webhook: Invalid JSON');

            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        /**
         * REAL RAZORPAY:
         * {
         *   "id": "evt_xxx",
         *   "event": "payment_link.paid",
         *   "payload": { ... }
         * }
         *
         * MANUAL / BROKEN:
         * no id, no event
         */
        $eventId = $payload['id']
            ?? data_get($payload, 'payload.payment.entity.id')
            ?? data_get($payload, 'payload.payment_link.entity.id')
            ?? uniqid('local_evt_', true);

        $eventType = $payload['event'] ?? 'manual.test';

        // ---------- IDEMPOTENCY ----------
        // For 'paid' events, check payment_id too
        if ($eventType === 'payment_link.paid'
            && WebhookLog::where('external_id', $eventId)->whereNotNull('razorpay_payment_id')->exists()
        ) {
            Log::info("Webhook already processed: {$eventId}");

            return response()->json(['status' => 'already_processed'], 200);
        }

        // For expired/cancelled events, check external_id only
        if (in_array($eventType, ['payment_link.expired', 'payment_link.cancelled'])
            && WebhookLog::where('external_id', $eventId)->exists()
        ) {
            Log::info("Webhook already processed: {$eventId}");

            return response()->json(['status' => 'already_processed'], 200);
        }

        // ---------- LOG FIRST ----------
        $log = WebhookLog::create([
            'event' => $eventType,
            'provider' => 'razorpay',
            'external_id' => $eventId,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        try {
            $this->processEvent($eventType, $payload);

            $log->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            return response()->json(['status' => 'ok'], 200);

        } catch (\Throwable $e) {

            Log::error("Webhook failed {$eventId}: ".$e->getMessage());

            $log->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'failed'], 500);
        }
    }

    // ------------------------------------------------------

    protected function processEvent(string $event, array $data): void
    {
        switch ($event) {

            case 'payment_link.paid':

                $paymentLinkId =
                    data_get($data, 'payload.payment_link.entity.id')
                    ?? data_get($data, 'payload.payment.entity.notes.payment_link_id')
                    ?? data_get($data, 'payload.payment.entity.notes.plink_id')
                    ?? null;
                $paymentId = data_get($data, 'payload.payment.entity.id');
                if (! $paymentId) {
                    throw new \Exception('Missing payment_id');
                }

                Log::info("Payment Success: {$paymentId}");

                $this->paymentService
                    ->handlePaymentSuccess($paymentLinkId, $paymentId);

                break;

            case 'payment_link.expired':
            case 'payment_link.cancelled':

                $paymentLinkId =
                    data_get($data, 'payload.payment_link.entity.id')
                    ?? data_get($data, 'payload.payment.entity.notes.payment_link_id')
                    ?? data_get($data, 'payload.payment.entity.notes.plink_id')
                    ?? null;
                Log::critical('EXTRACTED IDS for expired/cancelled', [
                    'payment_link_id' => $paymentLinkId,
                ]);
                if (! $paymentLinkId) {
                    throw new \Exception('Missing payment_link_id');
                }

                $status = $event === 'payment_link.expired'
                    ? 'expired'
                    : 'cancelled';

                $this->paymentService
                    ->updatePaymentStatus($paymentLinkId, $status);

                break;

            default:
                Log::info("Unhandled event: {$event}");
        }
    }
}
