<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Example Controller
 * 
 * This controller demonstrates how to use the standardized API response system.
 * Use this as a reference when creating your own API controllers.
 */
class ExampleController extends BaseApiController
{
    /**
     * Get all items (with pagination)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Example: Get paginated data
        // $items = YourModel::paginate($this->getPerPage($request));
        // return $this->paginated($items, 'Items retrieved successfully');

        // For this example, return mock data
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3'],
        ];

        return $this->success($items, 'Items retrieved successfully');
    }

    /**
     * Get a single item
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Example: Find item by ID
        // $item = YourModel::find($id);
        
        // if (!$item) {
        //     return $this->notFound('Item not found');
        // }

        // For this example, return mock data
        if ($id > 10) {
            return $this->notFound('Item not found');
        }

        $item = ['id' => $id, 'name' => 'Item ' . $id];

        return $this->success($item, 'Item retrieved successfully');
    }

    /**
     * Create a new item
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $this->validateRequest($request, [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            // Example: Create item
            // $item = YourModel::create($validated);

            // For this example, return mock data
            $item = [
                'id' => rand(1, 1000),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ];

            return $this->created($item, 'Item created successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError(
                $e->errors(),
                'Validation failed'
            );
        }
    }

    /**
     * Update an existing item
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Example: Find item
        // $item = YourModel::find($id);
        
        // if (!$item) {
        //     return $this->notFound('Item not found');
        // }

        if ($id > 10) {
            return $this->notFound('Item not found');
        }

        try {
            // Validate request
            $validated = $this->validateRequest($request, [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
            ]);

            // Example: Update item
            // $item->update($validated);

            // For this example, return mock data
            $item = [
                'id' => $id,
                'name' => $validated['name'] ?? 'Item ' . $id,
                'description' => $validated['description'] ?? null,
            ];

            return $this->success($item, 'Item updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError(
                $e->errors(),
                'Validation failed'
            );
        }
    }

    /**
     * Delete an item
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // Example: Find and delete item
        // $item = YourModel::find($id);
        
        // if (!$item) {
        //     return $this->notFound('Item not found');
        // }
        
        // $item->delete();

        if ($id > 10) {
            return $this->notFound('Item not found');
        }

        return $this->deleted('Item deleted successfully');
    }

    /**
     * Example: Handle authorization
     *
     * @return JsonResponse
     */
    public function unauthorized(): JsonResponse
    {
        return $this->unauthorized('You must be logged in to access this resource');
    }

    /**
     * Example: Handle forbidden access
     *
     * @return JsonResponse
     */
    public function forbidden(): JsonResponse
    {
        return $this->forbidden('You do not have permission to access this resource');
    }

    /**
     * Example: Handle errors
     *
     * @return JsonResponse
     */
    public function error(): JsonResponse
    {
        return $this->error('Something went wrong while processing your request');
    }
}
