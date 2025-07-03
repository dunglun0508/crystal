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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name')->nullable();
            $table->string('art_no')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('stock_status')->nullable(); // "Skladem", "Akce", etc.
            $table->string('variant_image')->nullable();
            $table->timestamps();
            
            $table->foreign('code')->references('slug')->on('products')->onDelete('cascade');
            $table->index(['code', 'art_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
}; 