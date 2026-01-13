# API Response Standards

## Overview

This document defines the standardized response format for all API endpoints in the LinkPeak backend. All API responses follow a consistent structure to ensure predictability and ease of integration.

## Response Format

### Success Response

All successful API responses return HTTP status code **200** with the following JSON structure:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": [
    // Your data here
  ]
}
```

### Error Response

All error responses also return HTTP status code **200** with the following JSON structure:

```json
{
  "success": false,
  "message": "Error description",
  "data": []
}
```

### Key Principles

1. **Always HTTP 200**: All responses return HTTP 200 status code
2. **Success Flag**: The `success` boolean indicates operation outcome
3. **Consistent Structure**: All responses have `success`, `message`, and `data` fields
4. **Empty Data on Error**: Error responses have empty `data` array

## Response Types

### 1. Success Response

Used for successful operations.

```php
return $this->success(
    $data,
    'Operation successful'
);
```

**Example:**
```json
{
  "success": true,
  "message": "User retrieved successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

### 2. Error Response

Used for general errors.

```php
return $this->error(
    'Something went wrong',
    []
);
```

**Example:**
```json
{
  "success": false,
  "message": "Something went wrong",
  "data": []
}
```

### 3. Validation Error Response

Used for validation failures.

```php
return $this->validationError(
    $validator->errors()->toArray(),
    'Validation failed'
);
```

**Example:**
```json
{
  "success": false,
  "message": "Validation failed",
  "data": {
    "errors": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  }
}
```

### 4. Not Found Response

Used when a resource is not found.

```php
return $this->notFound('User not found');
```

**Example:**
```json
{
  "success": false,
  "message": "User not found",
  "data": []
}
```

### 5. Unauthorized Response

Used for authentication failures.

```php
return $this->unauthorized('Invalid credentials');
```

**Example:**
```json
{
  "success": false,
  "message": "Invalid credentials",
  "data": []
}
```

### 6. Forbidden Response

Used when user lacks permissions.

```php
return $this->forbidden('You do not have permission to access this resource');
```

**Example:**
```json
{
  "success": false,
  "message": "You do not have permission to access this resource",
  "data": []
}
```

### 7. Created Response

Used when a resource is successfully created.

```php
return $this->created(
    $user,
    'User created successfully'
);
```

**Example:**
```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

### 8. Deleted Response

Used when a resource is successfully deleted.

```php
return $this->deleted('User deleted successfully');
```

**Example:**
```json
{
  "success": true,
  "message": "User deleted successfully",
  "data": []
}
```

### 9. Paginated Response

Used for paginated data.

```php
$users = User::paginate(15);
return $this->paginated($users, 'Users retrieved successfully');
```

**Example:**
```json
{
  "success": true,
  "message": "Users retrieved successfully",
  "data": {
    "items": [
      {"id": 1, "name": "John Doe"},
      {"id": 2, "name": "Jane Smith"}
    ],
    "pagination": {
      "total": 100,
      "per_page": 15,
      "current_page": 1,
      "last_page": 7,
      "from": 1,
      "to": 15
    }
  }
}
```

## Implementation

### Using ApiResponseTrait in Controllers

All API controllers should extend `BaseApiController` which includes the `ApiResponseTrait`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseApiController
{
    public function index()
    {
        $users = User::paginate($this->getPerPage(request()));
        
        return $this->paginated($users, 'Users retrieved successfully');
    }

    public function show($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return $this->notFound('User not found');
        }
        
        return $this->success($user, 'User retrieved successfully');
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
            ]);

            $user = User::create($validated);
            
            return $this->created($user, 'User created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError(
                $e->errors(),
                'Validation failed'
            );
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return $this->notFound('User not found');
        }
        
        $user->delete();
        
        return $this->deleted('User deleted successfully');
    }
}
```

### Using ApiResponse Helper (Outside Controllers)

For middleware, services, or other classes:

```php
use App\Helpers\ApiResponse;

// In middleware or service
return ApiResponse::error('Unauthorized access');
return ApiResponse::success($data, 'Operation successful');
```

## Security Features

### 1. Rate Limiting

All API routes are protected by rate limiting:

- **Default**: 60 requests per minute for authenticated users
- **Public routes**: 30 requests per minute

**Headers:**
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests

**Rate Limit Exceeded Response:**
```json
{
  "success": false,
  "message": "Too many requests. Please try again in 45 seconds.",
  "data": {
    "retry_after": 45
  }
}
```

### 2. Security Headers

All API responses include security headers:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`

### 3. CORS Configuration

Configure allowed origins in `.env`:

```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://yourdomain.com
```

### 4. Authentication

Use Laravel Sanctum for API authentication:

```php
// Protected route
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [UserController::class, 'show']);
});
```

## Exception Handling

All exceptions are automatically caught and formatted:

### Validation Exception
```json
{
  "success": false,
  "message": "Validation failed",
  "data": {
    "errors": {
      "field": ["Error message"]
    }
  }
}
```

### Authentication Exception
```json
{
  "success": false,
  "message": "Unauthenticated",
  "data": []
}
```

### Not Found Exception
```json
{
  "success": false,
  "message": "Resource not found",
  "data": []
}
```

### General Exception (Development)
```json
{
  "success": false,
  "message": "Error message",
  "data": {
    "exception": "ExceptionClass",
    "file": "/path/to/file.php",
    "line": 123,
    "trace": "..."
  }
}
```

### General Exception (Production)
```json
{
  "success": false,
  "message": "An error occurred while processing your request",
  "data": []
}
```

## Best Practices

### 1. Always Use Response Methods

❌ **Don't:**
```php
return response()->json(['user' => $user]);
```

✅ **Do:**
```php
return $this->success($user, 'User retrieved successfully');
```

### 2. Provide Meaningful Messages

❌ **Don't:**
```php
return $this->error('Error');
```

✅ **Do:**
```php
return $this->error('Unable to process payment. Please check your card details.');
```

### 3. Handle Exceptions Properly

❌ **Don't:**
```php
$user = User::findOrFail($id); // Throws exception
```

✅ **Do:**
```php
$user = User::find($id);
if (!$user) {
    return $this->notFound('User not found');
}
```

### 4. Use Appropriate Response Types

- `success()` - General success
- `created()` - Resource creation
- `deleted()` - Resource deletion
- `paginated()` - Paginated lists
- `error()` - General errors
- `notFound()` - Missing resources
- `unauthorized()` - Auth failures
- `forbidden()` - Permission issues
- `validationError()` - Validation failures

## Testing

### Example Test

```php
public function test_user_creation_returns_standardized_response()
{
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User created successfully',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'email',
            ]
        ]);
}
```

## Migration Guide

### Updating Existing Controllers

1. **Extend BaseApiController:**
```php
class UserController extends BaseApiController
{
    // Your methods
}
```

2. **Replace Response Calls:**

Before:
```php
return response()->json($user, 200);
```

After:
```php
return $this->success($user, 'User retrieved successfully');
```

3. **Update Error Handling:**

Before:
```php
return response()->json(['error' => 'Not found'], 404);
```

After:
```php
return $this->notFound('User not found');
```

## Environment Configuration

Add to `.env`:

```env
# CORS Configuration
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://yourdomain.com

# Rate Limiting (optional, defaults shown)
API_RATE_LIMIT=60
API_RATE_LIMIT_DECAY=1
```

## Summary

- ✅ All responses return HTTP 200
- ✅ Consistent `{success, message, data}` structure
- ✅ Built-in security features
- ✅ Automatic exception handling
- ✅ Easy to use trait and helper
- ✅ Comprehensive response types
- ✅ Rate limiting included
- ✅ CORS configured
