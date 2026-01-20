<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Traits\ImageUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BioPageController extends Controller
{
    use ImageUploadTrait;

    public function index(Request $request)
    {
        $pages = $request->user()->bioPages()
            ->select('id', 'user_id', 'slug', 'title', 'is_active', 'views', 'unique_views', 'created_at')
            ->get();

        return ApiResponse::success($pages, 'Bio pages retrieved successfully');
    }

    public function show(Request $request, $id, \App\Services\DashboardService $dashboardService)
    {
        $page = $request->user()->bioPages()->findOrFail($id);
        $data = $dashboardService->formatBioPage($page);

        return ApiResponse::success($data, 'Bio page retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|unique:bio_pages,slug|max:255',
            'title' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string',
            'template' => 'sometimes|string|in:classic,bento,hero,influencer,sleek,minimalist,glassmorphism,stack,brutalist,neobrutalism,darkneon,elegantserif,tiles,softpastel,gradientmesh,grid,social,modern',
            'theme' => 'sometimes|string',
        ]);

        $user = $request->user();

        if (! $user->canAccessFeature('pages', $user->bioPages()->count() + 1)) {
            return ApiResponse::error('Plan limit reached for pages. Please upgrade.', 403);
        }

        // Validate Template
        if ($request->has('template') && ! $user->canAccessFeature('allowedTemplates', $request->template)) {
            return ApiResponse::error('Upgrade to Pro/Agency to use this template.', 403);
        }

        // Validate Theme
        if ($request->has('theme') && ! $user->canAccessFeature('themes', $request->theme)) {
            return ApiResponse::error('Upgrade to Pro to use this theme.', 403);
        }

        $template = $request->template ?? 'classic';
        // Map legacy template IDs
        $templateMap = ['grid' => 'bento', 'social' => 'influencer', 'modern' => 'sleek'];
        $template = $templateMap[$template] ?? $template;

        $page = $user->bioPages()->create([
            'slug' => Str::slug($request->slug),
            'title' => $request->title ?? $user->name."'s Page",
            'bio' => $request->bio,
            'template' => $template,
            'theme' => $request->theme ?? 'light',
            'is_active' => true,
        ]);

        return ApiResponse::success($page, 'Bio page created successfully');
    }

    public function update(Request $request, $id, \App\Services\DashboardService $dashboardService)
    {
        $page = $request->user()->bioPages()->findOrFail($id);

        $request->validate([
            'slug' => 'sometimes|string|unique:bio_pages,slug,'.$id.'|max:255',
            'title' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string',
            'template' => 'sometimes|string|in:classic,bento,hero,influencer,sleek,minimalist,glassmorphism,stack,brutalist,neobrutalism,darkneon,elegantserif,tiles,softpastel,gradientmesh,grid,social,modern',
            'theme' => 'sometimes|string',
            'profile_image' => 'sometimes|nullable|string',
            'profile_image_file' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'is_sensitive' => 'sometimes|boolean',
            'show_branding' => 'sometimes|boolean',
            'seo' => 'sometimes|array',
            'social_links' => 'sometimes|array',
            'branding' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        // Validate Template
        if ($request->has('template') && ! $user->canAccessFeature('allowedTemplates', $request->template)) {
            return ApiResponse::error('Upgrade to Pro/Agency to use this template.', 403);
        }

        // Validate Theme
        if ($request->has('theme') && ! $user->canAccessFeature('themes', $request->theme)) {
            return ApiResponse::error('Upgrade to Pro to use this theme.', 403);
        }

        // Validate SEO
        if ($request->has('seo') && ! $user->canAccessFeature('seo')) {
            return ApiResponse::error('Upgrade to Pro to customize SEO settings.', 403);
        }

        // Validate Branding
        if ($request->has('branding')) {
            if ($request->input('branding.removeWatermark') && ! $user->canAccessFeature('removeWatermark')) {
                return ApiResponse::error('Upgrade to Pro to remove watermark.', 403);
            }
            if ($request->input('branding.customText') || $request->input('branding.customUrl')) {
                if (! $user->canAccessFeature('customBranding')) {
                    return ApiResponse::error('Upgrade to Agency for custom branding.', 403);
                }
            }
        }

        $data = $request->except(['profile_image_file']); // Exclude file field
        if (isset($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }

        if (isset($data['template'])) {
            $templateMap = ['grid' => 'bento', 'social' => 'influencer', 'modern' => 'sleek'];
            $data['template'] = $templateMap[$data['template']] ?? $data['template'];
        }

        if ($request->hasFile('profile_image_file')) {
            $this->deleteImage($page->profile_image);
            $path = $this->uploadImage($request->file('profile_image_file'), 'pages', 800, 800);
            $data['profile_image'] = $path;
        }

        $page->update($data);
        $page = $page->fresh(); // Reload with new data and accessors
        
        $data = $dashboardService->formatBioPage($page);

        return ApiResponse::success($data, 'Bio page updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        $page = $request->user()->bioPages()->findOrFail($id);
        $page->delete();

        return ApiResponse::success([], 'Bio page deleted successfully');
    }

}
