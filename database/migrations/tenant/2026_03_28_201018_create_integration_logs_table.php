<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('request_id')->nullable()->index();
            $table->uuid('trace_id')->nullable()->index();
            $table->string('system_name', 120)->index();
            $table->string('direction', 20)->index();
            $table->string('operation', 120)->index();
            $table->string('endpoint', 500)->nullable();
            $table->string('external_identifier', 150)->nullable()->index();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->string('processing_status', 30)->nullable()->index();
            $table->text('message')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at', 'system_name', 'processing_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};