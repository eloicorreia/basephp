<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_menu_item_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_menu_item_id')->constrained('admin_menu_items')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['admin_menu_item_id', 'permission_id']);
            $table->index('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_menu_item_permissions');
    }
};
