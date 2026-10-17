<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->uuid('adjustment_key')->nullable();
            $table->unique(['user_id', 'adjustment_key'], 'inventory_adjustment_retry_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropUnique('inventory_adjustment_retry_unique');
            $table->dropColumn('adjustment_key');
        });
    }
};
