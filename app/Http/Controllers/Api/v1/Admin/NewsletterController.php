<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\NewsletterSubscriber;
use App\Helpers\ApiResponse;

class NewsletterController extends Controller
{
    /**
     * List all newsletter subscribers.
     */
    public function index()
    {
        $subscribers = NewsletterSubscriber::orderBy('created_at', 'desc')->get();
        return ApiResponse::success($subscribers, 'Subscribers retrieved successfully');
    }

    /**
     * Remove a subscriber.
     */
    public function destroy($id)
    {
        $subscriber = NewsletterSubscriber::findOrFail($id);
        $subscriber->delete();

        return ApiResponse::success([], 'Subscriber removed successfully');
    }
}
