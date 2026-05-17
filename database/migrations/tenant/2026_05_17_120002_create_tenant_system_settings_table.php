<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_system_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('timezone', 80)->default('America/Sao_Paulo');
            $table->string('locale', 20)->default('pt_BR');
            $table->string('date_format', 30)->default('d/m/Y');
            $table->string('datetime_format', 40)->default('d/m/Y H:i');
            $table->unsignedSmallInteger('default_items_per_page')->default(15);
            $table->unsignedSmallInteger('max_items_per_page')->default(100);
            $table->string('support_email', 150)->nullable();
            $table->string('support_phone', 40)->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_system_settings');
    }
};
