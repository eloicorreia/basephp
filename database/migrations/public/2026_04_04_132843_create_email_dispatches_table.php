<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_dispatches', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('request_id')->nullable()->index();
            $table->uuid('trace_id')->nullable()->index();

            $table->unsignedBigInteger('requested_by_user_id')->nullable()->index();
            $table->string('requested_by_role', 100)->nullable();

            $table->string('queue', 100)->default('notifications')->index();

            $table->json('to_recipients');
            $table->json('cc_recipients')->nullable();
            $table->json('bcc_recipients')->nullable();

            $table->string('subject', 255);
            $table->text('body');
            $table->boolean('is_html')->default(false);

            $table->string('status', 30)->index();
            $table->string('provider', 100)->nullable();
            $table->string('provider_message_id', 255)->nullable()->index();
            $table->string('external_reference', 100)->nullable()->index();

            $table->unsignedInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_dispatches');
    }
};