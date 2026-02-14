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

    public function createOrder($amount, $currency = 'USD', $metadata = [])
    {
        try {
            return $this->api->order->create([
                'amount' => $amount * 100, // Amount in paise
                'currency' => $currency,
                'receipt' => 'receipt_'.time(),
                'notes' => $metadata,
            ]);
        } catch (Exception $e) {
            Log::error('Razorpay Create Order Failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function createPaymentLink($amount, $currency, $description, $metadata = [], $customer = null)
    {
        try {
            $payload = [
                'amount' => $amount * 100,
                'currency' => $currency,
                'description' => $description,
                'notes' => $metadata,
                'callback_url' => config('app.public_url') . '/dashboard?payment=success',
                'callback_method' => 'get',
            ];

            if ($customer) {
                $payload['customer'] = [
                    'name' => $customer->name ?? '',
                    'email' => $customer->email ?? '',
                    'contact' => $customer->contact ?? '',
                ];
            }

            return $this->api->paymentLink->create($payload);
        } catch (Exception $e) {
            Log::error('Razorpay Create Payment Link Failed: '.$e->getMessage());
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
