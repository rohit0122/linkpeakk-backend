<?php

namespace App\Services;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    protected $razorpayService;
    protected $paymentService;

    public function __construct(RazorpayService $razorpayService, PaymentService $paymentService)
    {
        $this->razorpayService = $razorpayService;
        $this->paymentService = $paymentService;
    }

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $secret = config('services.razorpay.webhook_secret');

        // Verify Signature
        if (!$this->razorpayService->verifyWebhookSignature($payload, $signature, $secret)) {
             Log::warning("Razorpay Webhook Signature Verification Failed.");
             return;
        }

        $data = json_decode($payload, true);
        $eventId = $data['id'] ?? null; // Razorpay sends 'id' for webhook event id
        $eventFn = $data['event'] ?? null;

        if (!$eventId) {
            return;
        }

        // Idempotency Check
        if (WebhookLog::where('event_id', $eventId)->exists()) {
            Log::info("Razorpay Webhook Event {$eventId} already processed.");
            return;
        }

        // Process Event
        try {
            $this->processEvent($eventFn, $data);
            
            // Log Success
            WebhookLog::create([
                'event_id' => $eventId,
                'event_type' => $eventFn,
                'payload' => $data,
                'status' => 'processed',
            ]);

        } catch (\Exception $e) {
            Log::error("Error processing Razorpay webhook {$eventId}: " . $e->getMessage());
            
            WebhookLog::create([
                'event_id' => $eventId,
                'event_type' => $eventFn,
                'payload' => $data,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    protected function processEvent($event, $data)
    {
        switch ($event) {
            case 'order.paid':
            case 'payment.captured':
            case 'payment_link.paid':
                // For payment_link.paid, the order_id is in the standard payment entity as well
                $razorpayOrderId = $data['payload']['payment']['entity']['order_id'] ?? 
                                   $data['payload']['payment_link']['entity']['order_id'] ?? null;
                $razorpayPaymentId = $data['payload']['payment']['entity']['id'] ?? null;

                if ($razorpayOrderId && $razorpayPaymentId) {
                    Log::info("Processing payment success for Order/Link: {$razorpayOrderId}");
                    $this->paymentService->handlePaymentSuccess($razorpayOrderId, $razorpayPaymentId);
                }
                break;
                
            case 'payment_link.expired':
            case 'payment_link.cancelled':
                $razorpayOrderId = $data['payload']['payment_link']['entity']['order_id'] ?? null;
                $status = ($event === 'payment_link.expired') ? 'expired' : 'cancelled';

                if ($razorpayOrderId) {
                     Log::info("Processing payment {$status} for Link: {$razorpayOrderId}");
                     $this->paymentService->updatePaymentStatus($razorpayOrderId, $status);
                }
                break;

            default:
                Log::info("Unhandled Razorpay Event: " . $event);
        }
    }
}
