<?php

namespace App\Services;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    protected $razorpayService;
    protected $subscriptionService;

    public function __construct(RazorpayService $razorpayService, SubscriptionService $subscriptionService)
    {
        $this->razorpayService = $razorpayService;
        $this->subscriptionService = $subscriptionService;
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
        $eventId = $data['event_id'] ?? null;
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
            case 'subscription.authenticated':
            case 'subscription.activated':
                Log::info("Subscription Active/Authenticated: " . $data['payload']['subscription']['entity']['id']);
                $this->subscriptionService->activateSubscription($data['payload']['subscription']['entity']['id']);
                break;

            case 'subscription.charged':
                Log::info("Subscription Charged: " . $data['payload']['subscription']['entity']['id']);
                // Always sync on charge to ensure period dates are updated
                $this->subscriptionService->activateSubscription($data['payload']['subscription']['entity']['id']);
                break;
                
            case 'subscription.cancelled':
            case 'subscription.halted':
            case 'subscription.expired':
                Log::info("Subscription Terminanted/Halted: " . $event . " - " . $data['payload']['subscription']['entity']['id']);
                $razorpaySubscriptionId = $data['payload']['subscription']['entity']['id'];
                $this->subscriptionService->activateSubscription($razorpaySubscriptionId); // Sync the status first
                
                // If it's truly inactive, we might need to deactivate the user or downgrade
                // This depends on whether you want immediate suspension or just status update
                break;
                
            default:
                Log::info("Unhandled Razorpay Event: " . $event);
        }
    }
}
