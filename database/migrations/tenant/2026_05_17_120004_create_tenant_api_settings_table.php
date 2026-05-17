<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_api_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->boolean('api_enabled')->default(true);
            $table->unsignedInteger('api_rate_limit_per_minute')->default(60);
            $table->unsignedInteger('strict_rate_limit_per_minute')->default(20);
            $table->unsignedInteger('login_rate_limit_per_minute')->default(6);
            $table->unsignedSmallInteger('api_default_pagination_size')->default(15);
            $table->unsignedSmallInteger('api_max_pagination_size')->default(100);
            $table->boolean('api_require_correlation_id')->default(false);
            $table->jsonb('api_allowed_origins')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_api_settings');
    }
};
