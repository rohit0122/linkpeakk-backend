<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BioPage;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeController extends Controller
{
    /**
     * Generate a QR code for a bio page.
     */
    public function show($pageId)
    {
        $page = BioPage::findOrFail($pageId);
        
        // Use APP_URL or a specific frontend URL if defined
        $baseUrl = config('app.url');
        // Assuming frontend handles the slug at root /slug
        $url = rtrim($baseUrl, '/') . '/' . $page->slug;

        $qrCode = QrCode::size(300)
            ->format('svg')
            ->generate($url);

        return response($qrCode)->header('Content-Type', 'image/svg+xml');
    }

    /**
     * Generate SVG QR code for embedding.
     */
    public function svg($pageId)
    {
        $page = BioPage::findOrFail($pageId);
        $baseUrl = config('app.url');
        $url = rtrim($baseUrl, '/') . '/' . $page->slug;

        $qrCode = QrCode::size(200)
            ->generate($url);

        return response($qrCode)->header('Content-Type', 'image/svg+xml');
    }
}
