<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BioPage;
use App\Models\Lead;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * Display a listing of leads for a specific bio page.
     */
    public function index(Request $request, $pageId)
    {
        $page = $request->user()->bioPages()->findOrFail($pageId);
        
        $leads = $page->leads()
            ->latest()
            ->paginate(20);

        return ApiResponse::success($leads, 'Leads retrieved successfully');
    }

    /**
     * Store a newly created lead in storage (Public).
     */
    public function store(Request $request)
    {
        $request->validate([
            'bio_page_id' => 'required|exists:bio_pages,id',
            'name' => 'sometimes|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'sometimes|string',
            'metadata' => 'sometimes|array',
        ]);

        $lead = Lead::create([
            'bio_page_id' => $request->bio_page_id,
            'name' => $request->name,
            'email' => $request->email,
            'message' => $request->message,
            'metadata' => $request->metadata,
        ]);

        return ApiResponse::success($lead, 'Lead submitted successfully', 201);
    }
}
