<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\NewsletterSubscriber;
use App\Helpers\ApiResponse;

class NewsletterController extends Controller
{
    /**
     * Subscribe to the newsletter.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:newsletter_subscribers,email',
        ], [
            'email.unique' => 'You are already subscribed to our newsletter!',
        ]);

        NewsletterSubscriber::create($validated);

        return ApiResponse::success([], 'Thank you for subscribing to our newsletter!');
    }
}
