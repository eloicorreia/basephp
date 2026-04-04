<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class EmailDispatchLog extends Model
{
    protected $table = 'email_dispatch_logs';

    protected $fillable = [
        'request_id',
        'trace_id',
        'tenant_id',
        'tenant_code',
        'user_id',
        'trigger',
        'status',
        'attempt_count',
        'mail_config_id',
        'mail_config_name',
        'driver',
        'host',
        'port',
        'encryption',
        'from_address',
        'from_name',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'subject',
        'html_body',
        'text_body',
        'queue_connection',
        'queue_name',
        'job_uuid',
        'provider_message_id',
        'idempotency_key',
        'error_class',
        'error_message',
        'stack_trace_summary',
        'queued_at',
        'sending_started_at',
        'sent_at',
        'failed_at',
        'context',
    ];

    protected $casts = [
        'to_recipients' => 'array',
        'cc_recipients' => 'array',
        'bcc_recipients' => 'array',
        'context' => 'array',
        'queued_at' => 'datetime',
        'sending_started_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}