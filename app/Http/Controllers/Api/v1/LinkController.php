<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\BioPage;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['pageId' => 'required|exists:bio_pages,id']);
        $pageId = $request->pageId;
 
        // Ensure user owns the page
        $page = $request->user()->bioPages()->findOrFail($pageId);
        $links = $page->links()
            ->select('id', 'user_id', 'bio_page_id', 'title', 'url', 'icon', 'is_active', 'order', 'clicks', 'unique_clicks', 'created_at', 'updated_at')
            ->orderBy('order')
            ->get();
 
        return ApiResponse::success($links, 'Links retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'pageId' => 'required|exists:bio_pages,id',
            'title' => 'required|string|max:255',
            'url' => 'required|url',
            'icon' => 'sometimes|nullable|string|max:10',
        ]);

        $user = $request->user();
        $page = $user->bioPages()->findOrFail($request->pageId);

        if (!$user->canAccessFeature('links', $page->links()->count() + 1)) {
            return ApiResponse::error('Plan limit reached. Upgrade to create more links.', 403);
        }

        $link = $page->links()->create([
            'user_id' => $user->id, 
            'title' => $request->title,
            'url' => $request->url,
            'icon' => $request->icon,
            'is_active' => true,
            'order' => $page->links()->count(),
        ]);

        // Return all links for the page
        $allLinks = $page->links()
            ->select('id', 'user_id', 'bio_page_id', 'title', 'url', 'icon', 'is_active', 'order', 'clicks', 'unique_clicks', 'created_at', 'updated_at')
            ->orderBy('order')
            ->get();

        return ApiResponse::success($allLinks, 'Link created successfully');
    }

    public function update(Request $request, $id)
    {
        $link = $request->user()->links()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'icon' => 'sometimes|nullable|string|max:10',
            'is_active' => 'sometimes|boolean',
            'order' => 'sometimes|integer',
        ]);

        $link->update($validated);

        // Return all links for the page
        $allLinks = $link->bioPage->links()
            ->select('id', 'user_id', 'bio_page_id', 'title', 'url', 'icon', 'is_active', 'order', 'clicks', 'unique_clicks', 'created_at', 'updated_at')
            ->orderBy('order')
            ->get();

        return ApiResponse::success($allLinks, 'Link updated successfully');
    }

    public function bulkReorder(Request $request)
    {
        $request->validate([
            'links' => 'required|array',
            'links.*.id' => 'required|exists:links,id',
            'links.*.order' => 'required|integer'
        ]);

        $firstLink = null;
        foreach ($request->links as $item) {
            $link = Link::where('id', $item['id'])
                ->where('user_id', $request->user()->id)
                ->first();
            
            if ($link) {
                $link->update(['order' => $item['order']]);
                if (!$firstLink) $firstLink = $link;
            }
        }

        if ($firstLink) {
            // Updatedat on parent will be updated via $touches if we iterate, 
            // but bulk reorder might need a manual touch if we use a raw query.
            // Since we are using $link->update() in the loop, $touches works.
        }

        return ApiResponse::success([], 'Links reordered successfully');
    }

    public function destroy(Request $request, $id)
    {
        $link = $request->user()->links()->findOrFail($id);
        $link->delete();

        return ApiResponse::success([], 'Link deleted successfully');
    }
}
