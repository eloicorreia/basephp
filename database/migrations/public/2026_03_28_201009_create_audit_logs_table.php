<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('request_id')->index();
            $table->uuid('correlation_id')->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('tenant_code', 100)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('entity_type', 150)->index();
            $table->string('entity_id', 100)->index();
            $table->string('action', 50)->index();
            $table->jsonb('before_data')->nullable();
            $table->jsonb('after_data')->nullable();
            $table->text('message')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['created_at', 'entity_type', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};