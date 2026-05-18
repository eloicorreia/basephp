<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_provisioning_runs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('tenant_code', 50)->index();
            $table->string('schema_name', 63)->index();
            $table->string('operation', 100)->index();
            $table->string('status', 30)->index();
            $table->timestamp('started_at')->index();
            $table->timestamp('finished_at')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->string('error_class', 255)->nullable();
            $table->uuid('request_id')->nullable()->index();
            $table->uuid('trace_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_provisioning_runs');
    }
};
