<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('request_id')->nullable()->index();
            $table->uuid('trace_id')->nullable()->index();
            $table->string('level', 20)->index();
            $table->string('category', 50)->index();
            $table->string('service', 100)->nullable()->index();
            $table->string('operation', 100)->nullable()->index();
            $table->string('route', 255)->nullable();
            $table->string('method', 10)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->json('input_payload')->nullable();
            $table->json('output_payload')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->string('processing_status', 30)->nullable()->index();
            $table->text('stack_trace_summary')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};