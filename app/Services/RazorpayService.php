<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class RazorpayService
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api(config('services.razorpay.key_id'), config('services.razorpay.key_secret'));
    }

    public function createCustomer($name, $email, $contact = null)
    {
        try {
            return $this->api->customer->create([
                'name' => $name,
                'email' => $email,
                'contact' => $contact,
            ]);
        } catch (Exception $e) {
            Log::error('Razorpay Create Customer Failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function getCustomerByEmail($email)
    {
        try {
            $customers = $this->api->customer->all(['email' => $email]);
            foreach ($customers as $customer) {
                return $customer;
            }

            return null;
        } catch (Exception $e) {
            Log::error('Razorpay Get Customer By Email Failed: '.$e->getMessage());

            return null;
        }
    }

    public function getOrCreateCustomer($name, $email, $contact = null)
    {
        // 1. Try to find by email
        $customer = $this->getCustomerByEmail($email);
        if ($customer) {
            // Check if it's an object or array and return the id
            return is_object($customer) ? $customer->id : ($customer['id'] ?? null);
        }

        // 2. If not found, create
        $newCustomer = $this->createCustomer($name, $email, $contact);

        return is_object($newCustomer) ? $newCustomer->id : ($newCustomer['id'] ?? null);
    }

    public function createSubscription($planId, $customerId, $totalCount = 120, $startAt = null)
    {
        try {
            $data = [
                'plan_id' => $planId,
                'total_count' => $totalCount,
                'quantity' => 1,
                'customer_id' => $customerId,
            ];

            if ($startAt) {
                $data['start_at'] = $startAt; // Timestamp for trial end or future start
            }

            // Add ons can be handled here if needed

            return $this->api->subscription->create($data);
        } catch (Exception $e) {
            Log::error('Razorpay Create Subscription Failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function cancelSubscription($subscriptionId, $cancelAtCycleEnd = false)
    {
        try {
            return $this->api->subscription->fetch($subscriptionId)->cancel(['cancel_at_cycle_end' => $cancelAtCycleEnd ? 1 : 0]);
        } catch (Exception $e) {
            Log::error('Razorpay Cancel Subscription Failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function fetchSubscription($subscriptionId)
    {
        try {
            return $this->api->subscription->fetch($subscriptionId);
        } catch (Exception $e) {
            Log::error('Razorpay Fetch Subscription Failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function listSubscriptions($options = [])
    {
        try {
            return $this->api->subscription->all($options);
        } catch (Exception $e) {
            Log::error('Razorpay List Subscriptions Failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function verifyWebhookSignature($webhookBody, $webhookSignature, $webhookSecret)
    {
        try {
            $this->api->utility->verifyWebhookSignature($webhookBody, $webhookSignature, $webhookSecret);

            return true;
        } catch (Exception $e) {
            Log::error('Razorpay Webhook Signature Verification Failed: '.$e->getMessage());

            return false;
        }
    }

    public function verifyPaymentSignature($attributes)
    {
        try {
            $this->api->utility->verifyPaymentSignature($attributes);

            return true;
        } catch (Exception $e) {
            Log::error('Razorpay Payment Signature Verification Failed: '.$e->getMessage());

            return false;
        }
    }
}
