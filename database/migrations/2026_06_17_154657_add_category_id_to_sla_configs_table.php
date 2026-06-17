<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sla_configs', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained()->nullOnDelete();
            // unique constraint: priority+category (null category = global default)
            $table->unique(['priority', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sla_configs', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropUnique(['priority', 'category_id']);
            $table->dropColumn('category_id');
        });
    }
};
