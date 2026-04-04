<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\EmailDispatchListRequest;
use App\Http\Requests\Api\V1\Admin\SendEmailRequest;
use App\Http\Resources\Api\V1\EmailDispatchResource;
use App\Models\EmailDispatch;
use App\Services\Mail\EmailDispatchService;
use Illuminate\Http\JsonResponse;

final class EmailDispatchController extends Controller
{
    public function __construct(
        private readonly EmailDispatchService $emailDispatchService,
    ) {
    }

    public function index(EmailDispatchListRequest $request): JsonResponse
    {
        $items = $this->emailDispatchService->list(
            status: $request->validated('status'),
            perPage: (int) $request->validated('per_page', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => EmailDispatchResource::collection($items->items()),
            'meta' => [
                'page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function show(EmailDispatch $emailDispatch): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => new EmailDispatchResource($emailDispatch),
        ]);
    }

    public function send(SendEmailRequest $request): JsonResponse
    {
        $emailDispatch = $this->emailDispatchService->dispatch(
            payload: $request->validated(),
            actorId: auth()->id(),
            actorRole: auth()->user()?->role?->code,
        );

        return response()->json([
            'success' => true,
            'message' => 'E-mail enviado para processamento com sucesso.',
            'data' => new EmailDispatchResource($emailDispatch),
        ], 202);
    }

    public function retry(EmailDispatch $emailDispatch): JsonResponse
    {
        $retried = $this->emailDispatchService->retry(
            emailDispatch: $emailDispatch,
            actorId: auth()->id(),
            actorRole: auth()->user()?->role?->code,
        );

        return response()->json([
            'success' => true,
            'message' => 'Reenvio de e-mail solicitado com sucesso.',
            'data' => new EmailDispatchResource($retried),
        ], 202);
    }
}