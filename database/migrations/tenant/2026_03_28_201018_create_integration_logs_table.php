<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('request_id')->index();
            $table->uuid('correlation_id')->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('tenant_code', 100)->nullable()->index();
            $table->string('system_name', 120)->index();
            $table->string('operation', 120)->index();
            $table->string('direction', 20)->index();
            $table->string('external_identifier', 150)->nullable()->index();
            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->string('status', 30)->index();
            $table->jsonb('request_payload')->nullable();
            $table->jsonb('response_payload')->nullable();
            $table->text('message')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['created_at', 'system_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};