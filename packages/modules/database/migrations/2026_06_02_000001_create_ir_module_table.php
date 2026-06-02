<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Velm\Modules\ModuleRepository;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(ModuleRepository::TABLE, static function (Blueprint $table): void {
            $table->string('name')->primary();
            $table->string('version');
            $table->timestamp('installed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ModuleRepository::TABLE);
    }
};
