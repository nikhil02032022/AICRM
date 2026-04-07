<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ApiResponse — Standard A2A-CRM API response envelope.
 *
 * All API responses must use: { success, data, message, meta }
 * Error responses must use:   { success: false, error: { code, message, field? } }
 *
 * BRD: API Design Standards — versioned /api/v1/crm/ envelope.
 */
trait ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function successPaginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
    ): JsonResponse {
        return $this->success(
            data: $paginator->items(),
            message: $message,
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        );
    }

    protected function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function error(
        string $message = 'An error occurred',
        string $code = 'ERROR',
        int $status = 400,
        ?string $field = null,
    ): JsonResponse {
        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($field !== null) {
            $payload['error']['field'] = $field;
        }

        return response()->json($payload, $status);
    }

    protected function unauthorized(string $message = 'Unauthenticated'): JsonResponse
    {
        return $this->error($message, 'UNAUTHENTICATED', 401);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 'FORBIDDEN', 403);
    }

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 'NOT_FOUND', 404);
    }
}
