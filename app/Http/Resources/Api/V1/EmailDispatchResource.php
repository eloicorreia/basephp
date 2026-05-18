<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\EmailDispatch;
use App\Services\TenantSettings\TenantRuntimeSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EmailDispatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof EmailDispatch) {
            return [];
        }

        $emailDispatch = $this->resource;
        $runtimeSettings = app(TenantRuntimeSettings::class);

        return [
            'id' => $emailDispatch->id,
            'queue' => $emailDispatch->queue,
            'to_recipients' => $emailDispatch->to_recipients,
            'cc_recipients' => $emailDispatch->cc_recipients,
            'bcc_recipients' => $emailDispatch->bcc_recipients,
            'subject' => $emailDispatch->subject,
            'is_html' => $emailDispatch->is_html,
            'status' => $emailDispatch->status,
            'provider' => $emailDispatch->provider,
            'provider_message_id' => $emailDispatch->provider_message_id,
            'external_reference' => $emailDispatch->external_reference,
            'attempts' => $emailDispatch->attempts,
            'error_message' => $emailDispatch->error_message,
            'sent_at' => $runtimeSettings->formatDateTime($emailDispatch->sent_at),
            'created_at' => $runtimeSettings->formatDateTime($emailDispatch->created_at),
            'updated_at' => $runtimeSettings->formatDateTime($emailDispatch->updated_at),
        ];
    }
}
