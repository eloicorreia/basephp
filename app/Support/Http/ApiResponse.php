<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public const SUCCESS_MESSAGE = 'Operação realizada com sucesso.';

    public const RETRIEVED_MESSAGE = 'Dados recuperados com sucesso.';

    public const ERROR_MESSAGE = 'Erro ao processar a requisição.';

    public static function success(
        mixed $data = [],
        string $message = self::SUCCESS_MESSAGE,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function retrieved(mixed $data = []): JsonResponse
    {
        return self::success(
            data: $data,
            message: self::RETRIEVED_MESSAGE,
        );
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        mixed $data = null,
        string $message = self::RETRIEVED_MESSAGE
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?? $paginator->items(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * @param  array<mixed>  $errors
     */
    public static function error(
        string $message = self::ERROR_MESSAGE,
        array $errors = [],
        int $status = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
