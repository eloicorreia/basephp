<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('request_id')->nullable()->index();
            $table->uuid('trace_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('category', 50)->index();
            $table->string('operation', 100)->index();
            $table->string('entity_type', 150)->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('processing_status', 30)->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_logs');
    }
};