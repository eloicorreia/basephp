<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_menu_event_logs', function (Blueprint $table): void {
            $table->id();
            $table->timestampTz('occurred_at');
            $table->string('event', 120);
            $table->string('level', 20)->default('info');
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_id', 120)->nullable();
            $table->string('trace_id', 120)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('context')->nullable();
            $table->text('message')->nullable();
            $table->text('stack_summary')->nullable();
            $table->timestamps();

            $table->index(['event', 'occurred_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('request_id');
            $table->index('trace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_menu_event_logs');
    }
};
