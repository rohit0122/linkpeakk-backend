# API Response Quick Reference

## Import Statement

```php
use App\Traits\ApiResponseTrait;  // For controllers
use App\Helpers\ApiResponse;      // For anywhere else
```

## In Controllers

Extend `BaseApiController`:

```php
class YourController extends BaseApiController
{
    // Use trait methods directly
}
```

## Response Methods

### Success Responses

```php
// General success
$this->success($data, 'Message');

// Created
$this->created($data, 'Resource created');

// Deleted
$this->deleted('Resource deleted');

// Paginated
$this->paginated($paginator, 'Data retrieved');
```

### Error Responses

```php
// General error
$this->error('Error message');

// Not found
$this->notFound('Resource not found');

// Unauthorized
$this->unauthorized('Login required');

// Forbidden
$this->forbidden('No permission');

// Validation error
$this->validationError($errors, 'Validation failed');
```

## Common Patterns

### Get Single Resource

```php
public function show($id)
{
    $item = Model::find($id);
    
    if (!$item) {
        return $this->notFound('Item not found');
    }
    
    return $this->success($item, 'Item retrieved');
}
```

### Create Resource

```php
public function store(Request $request)
{
    try {
        $validated = $this->validateRequest($request, [
            'name' => 'required|string',
        ]);
        
        $item = Model::create($validated);
        
        return $this->created($item, 'Item created');
        
    } catch (ValidationException $e) {
        return $this->validationError($e->errors());
    }
}
```

### List Resources (Paginated)

```php
public function index(Request $request)
{
    $items = Model::paginate($this->getPerPage($request));
    
    return $this->paginated($items, 'Items retrieved');
}
```

### Update Resource

```php
public function update(Request $request, $id)
{
    $item = Model::find($id);
    
    if (!$item) {
        return $this->notFound('Item not found');
    }
    
    try {
        $validated = $this->validateRequest($request, [
            'name' => 'sometimes|required|string',
        ]);
        
        $item->update($validated);
        
        return $this->success($item, 'Item updated');
        
    } catch (ValidationException $e) {
        return $this->validationError($e->errors());
    }
}
```

### Delete Resource

```php
public function destroy($id)
{
    $item = Model::find($id);
    
    if (!$item) {
        return $this->notFound('Item not found');
    }
    
    $item->delete();
    
    return $this->deleted('Item deleted');
}
```

## Response Format

All responses follow this structure:

```json
{
  "success": true|false,
  "message": "Human readable message",
  "data": [] | {}
}
```

**Always HTTP 200 status code**

## Rate Limiting

Add to routes:

```php
// 60 requests per minute
Route::middleware(['api.rate.limit:60,1'])->group(function () {
    // routes
});

// 30 requests per minute
Route::middleware(['api.rate.limit:30,1'])->group(function () {
    // routes
});
```

## Authentication

```php
Route::middleware(['auth:sanctum'])->group(function () {
    // protected routes
});
```

## Using Outside Controllers

```php
use App\Helpers\ApiResponse;

return ApiResponse::success($data, 'Message');
return ApiResponse::error('Error message');
return ApiResponse::notFound('Not found');
```

## Environment Variables

```env
# CORS - comma separated origins or *
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://yourdomain.com

# Rate limiting
API_RATE_LIMIT=60
API_RATE_LIMIT_DECAY=1
```

## Testing Endpoints

```bash
# Health check
curl http://localhost:8000/api/health

# Example endpoints
curl http://localhost:8000/api/v1/examples
curl http://localhost:8000/api/v1/examples/1
curl -X POST http://localhost:8000/api/v1/examples -d '{"name":"Test"}'
```
