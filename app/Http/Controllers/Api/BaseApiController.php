<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

/**
 * Base API Controller
 * 
 * All API controllers should extend this class to inherit
 * standardized response methods and common functionality.
 */
class BaseApiController extends Controller
{
    use ApiResponseTrait;

    /**
     * Default pagination limit
     */
    protected int $perPage = 15;

    /**
     * Maximum pagination limit
     */
    protected int $maxPerPage = 100;

    /**
     * Get pagination limit from request
     *
     * @param \Illuminate\Http\Request $request
     * @return int
     */
    protected function getPerPage($request): int
    {
        $perPage = (int) $request->input('per_page', $this->perPage);
        
        return min($perPage, $this->maxPerPage);
    }

    /**
     * Validate request data
     *
     * @param \Illuminate\Http\Request $request
     * @param array $rules
     * @param array $messages
     * @return array
     */
    protected function validateRequest($request, array $rules, array $messages = []): array
    {
        return $request->validate($rules, $messages);
    }
}
