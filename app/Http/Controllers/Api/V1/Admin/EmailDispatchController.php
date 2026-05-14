<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\EmailDispatchListRequest;
use App\Http\Requests\Api\V1\Admin\SendEmailRequest;
use App\Http\Resources\Api\V1\EmailDispatchResource;
use App\Models\EmailDispatch;
use App\Services\Mail\EmailDispatchService;
use App\Support\Auth\AuthenticatedUserId;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

final class EmailDispatchController extends Controller
{
    public function __construct(
        private readonly EmailDispatchService $emailDispatchService,
    ) {}

    public function index(EmailDispatchListRequest $request): JsonResponse
    {
        $items = $this->emailDispatchService->list(
            status: $request->validated('status'),
            perPage: (int) $request->validated('per_page', 15),
        );

        return ApiResponse::paginated(
            paginator: $items,
            data: EmailDispatchResource::collection($items->items()),
        );
    }

    public function show(EmailDispatch $emailDispatch): JsonResponse
    {
        return ApiResponse::retrieved(new EmailDispatchResource($emailDispatch));
    }

    public function send(SendEmailRequest $request): JsonResponse
    {
        $emailDispatch = $this->emailDispatchService->dispatch(
            payload: $request->validated(),
            actorId: AuthenticatedUserId::resolve(),
            actorRole: auth()->user()?->role?->code,
        );

        return ApiResponse::success(
            data: new EmailDispatchResource($emailDispatch),
            message: 'E-mail enviado para processamento com sucesso.',
            status: 202,
        );
    }

    public function retry(EmailDispatch $emailDispatch): JsonResponse
    {
        $retried = $this->emailDispatchService->retry(
            emailDispatch: $emailDispatch,
            actorId: AuthenticatedUserId::resolve(),
            actorRole: auth()->user()?->role?->code,
        );

        return ApiResponse::success(
            data: new EmailDispatchResource($retried),
            message: 'Reenvio de e-mail solicitado com sucesso.',
            status: 202,
        );
    }
}
