<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_execution_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('request_id')->nullable()->index();
            $table->uuid('trace_id')->nullable()->index();

            $table->unsignedBigInteger('job_id')->nullable()->index();
            $table->uuid('job_uuid')->nullable()->index();
            $table->string('queue', 100)->index();
            $table->string('job_class', 255)->nullable()->index();

            $table->string('event', 50)->index();
            $table->unsignedInteger('attempt')->nullable();
            $table->string('status', 30)->nullable()->index();

            $table->text('message')->nullable();
            $table->json('context')->nullable();

            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['queue', 'event', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_execution_logs');
    }
};