<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\EmailDispatch;
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
            'sent_at' => $emailDispatch->sent_at,
            'created_at' => $emailDispatch->created_at,
            'updated_at' => $emailDispatch->updated_at,
        ];
    }
}
