<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('request_id')->index();
            $table->uuid('correlation_id')->index();
            $table->string('tenant_code', 100)->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('oauth_client_id', 100)->nullable()->index();
            $table->string('method', 10);
            $table->string('route', 255)->nullable()->index();
            $table->string('uri', 500);
            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->jsonb('request_headers')->nullable();
            $table->jsonb('request_query')->nullable();
            $table->jsonb('request_body')->nullable();
            $table->jsonb('response_body')->nullable();
            $table->string('processing_status', 30)->index();
            $table->text('message')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['created_at', 'processing_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};