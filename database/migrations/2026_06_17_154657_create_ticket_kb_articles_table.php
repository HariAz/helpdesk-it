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
        Schema::create('ticket_kb_articles', function (Blueprint $table) {
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kb_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attached_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['ticket_id', 'kb_article_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_kb_articles');
    }
};
