<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PaymentService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $razorpayService;

    public function __construct(PaymentService $paymentService, RazorpayService $razorpayService)
    {
        $this->paymentService = $paymentService;
        $this->razorpayService = $razorpayService;
    }

    /**
     * Create a new payment link.
     */
    public function createLink(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);

        try {
            $paymentUrl = $this->paymentService->createPaymentLink($user, $plan);

            return response()->json([
                'payment_url' => $paymentUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Verify payment signature (for client-side redirection).
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        $status = $this->razorpayService->verifyPaymentSignature([
            'razorpay_order_id' => $request->razorpay_order_id,
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature' => $request->razorpay_signature,
        ]);

        if (!$status) {
            return response()->json(['message' => 'Invalid payment signature'], 400);
        }

        // Process success
        $this->paymentService->handlePaymentSuccess($request->razorpay_order_id, $request->razorpay_payment_id);

        return response()->json(['message' => 'Payment successful']);
    }

    /**
     * Get current payment/subscription status.
     */
    public function getStatus(Request $request)
    {
        $dashboardService = app(\App\Services\DashboardService::class);
        $data = $dashboardService->formatSubscription($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Payment status retrieved successfully',
            'data' => [
                'subscription' => $data
            ]
        ]);
    }
}
