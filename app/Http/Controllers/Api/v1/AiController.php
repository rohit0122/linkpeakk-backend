<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\AiTitleSuggesterService;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    private $aiService;
    private $seoService;

    public function __construct(AiTitleSuggesterService $aiService, \App\Services\AiSeoSuggesterService $seoService)
    {
        $this->aiService = $aiService;
        $this->seoService = $seoService;
    }

    /**
     * Generate link title suggestions using AI.
     */
    public function generateLinkTitle(Request $request)
    {
        $request->validate([
            'url' => 'required|url|max:2048'
        ]);

        $url = $request->url;

        try {
            $result = $this->aiService->generateTitles($url);
            return ApiResponse::success($result, 'Title suggestions generated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to generate title suggestions', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate SEO suggestions for a bio page using AI.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSeo(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'slug' => 'required|string|max:100'
        ]);

        try {
            $result = $this->seoService->generateSeo(
                $request->title, 
                $request->bio ?? '', 
                $request->slug
            );
            return ApiResponse::success($result, 'SEO suggestions generated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to generate SEO suggestions', ['error' => $e->getMessage()], 500);
        }
    }
}
