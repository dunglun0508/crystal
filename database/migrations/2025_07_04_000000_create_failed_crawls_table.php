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
        Schema::create('failed_crawls', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'product', 'category_level2', 'category_level3', 'product_detail'
            $table->string('identifier'); // slug, code, url
            $table->text('error')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'identifier']);
            $table->index(['type', 'resolved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_crawls');
    }
}; 