<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authentication_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('request_id')->nullable()->index();
            $table->uuid('trace_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('username', 255)->nullable()->index();
            $table->string('tenant_code', 100)->nullable()->index();
            $table->string('oauth_client_id', 100)->nullable()->index();
            $table->string('event_type', 50)->index();
            $table->string('processing_status', 30)->index();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at', 'event_type', 'processing_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication_logs');
    }
};