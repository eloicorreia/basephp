<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_menu_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('version')->default(1);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('changed_at')->nullable();
            $table->timestamps();
        });

        DB::table('admin_menu_versions')->insert([
            'id' => 1,
            'version' => 1,
            'changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_menu_versions');
    }
};
