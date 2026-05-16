<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_menu_group_id')->nullable()->constrained('admin_menu_groups')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('admin_menu_items')->nullOnDelete();
            $table->string('code', 120)->unique();
            $table->string('title', 120);
            $table->string('translation_key', 180)->nullable();
            $table->string('route_name', 180)->nullable()->index();
            $table->string('active_route_pattern', 180)->nullable();
            $table->string('icon', 80)->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('active')->default(true)->index();
            $table->boolean('opens_in_new_tab')->default(false);
            $table->string('permission_strategy', 20)->default('any');
            $table->timestamps();

            $table->index(['admin_menu_group_id', 'active', 'order']);
            $table->index(['parent_id', 'active', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_menu_items');
    }
};
