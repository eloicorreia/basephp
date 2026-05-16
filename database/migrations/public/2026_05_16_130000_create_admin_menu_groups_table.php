<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_menu_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('title', 120);
            $table->string('translation_key', 180)->nullable();
            $table->string('icon', 80)->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_menu_groups');
    }
};
