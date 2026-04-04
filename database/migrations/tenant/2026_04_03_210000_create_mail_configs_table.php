<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mail_configs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name', 150);
            $table->string('driver', 20)->default('smtp');
            $table->string('host', 255);
            $table->unsignedInteger('port');
            $table->string('encryption', 20)->nullable();
            $table->string('username', 255)->nullable();
            $table->text('password_encrypted')->nullable();
            $table->string('from_address', 255);
            $table->string('from_name', 255);
            $table->string('reply_to_address', 255)->nullable();
            $table->string('reply_to_name', 255)->nullable();
            $table->unsignedSmallInteger('timeout_seconds')->default(30);
            $table->boolean('verify_peer')->default(true);
            $table->boolean('verify_peer_name')->default(true);
            $table->boolean('allow_self_signed')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_default'], 'mail_configs_active_default_idx');
            $table->index('from_address', 'mail_configs_from_address_idx');
        });

        DB::statement(
            'CREATE UNIQUE INDEX mail_configs_single_default_idx
             ON mail_configs (is_default)
             WHERE is_default = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_configs');
    }
};