<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Mail\ContactAdminEmail;
use App\Mail\ContactReceiptEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    /**
     * Handle contact form submission.
     */
    public function submit(Request $request)
    {

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string|max:5000',
            ]);

            // Send email to admin
            Mail::to(config('mail.from.address'))
                ->send(new ContactAdminEmail(
                    $validated['name'],
                    $validated['email'],
                    $validated['subject'],
                    $validated['message']
                ));

            // Send auto-reply receipt to user
            Mail::to($validated['email'])
                ->send(new ContactReceiptEmail(
                    $validated['name'],
                    $validated['subject']
                ));

            return response()->json([
                'success' => true,
                'message' => 'Thank you for contacting us. We will get back to you soon.',
                'data' => [],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: '.$e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
