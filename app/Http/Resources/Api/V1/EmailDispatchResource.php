<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EmailDispatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'queue' => $this->queue,
            'to_recipients' => $this->to_recipients,
            'cc_recipients' => $this->cc_recipients,
            'bcc_recipients' => $this->bcc_recipients,
            'subject' => $this->subject,
            'is_html' => $this->is_html,
            'status' => $this->status,
            'provider' => $this->provider,
            'provider_message_id' => $this->provider_message_id,
            'external_reference' => $this->external_reference,
            'attempts' => $this->attempts,
            'error_message' => $this->error_message,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}