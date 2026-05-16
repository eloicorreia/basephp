<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('name', 150);
            $table->string('description', 255)->nullable();
            $table->string('group', 80)->nullable()->index();
            $table->string('context', 20)->default('api')->index();
            $table->boolean('is_system')->default(false)->index();
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
