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
        Schema::create('product_details', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->text('gallery')->nullable();
            $table->text('gallery_local')->nullable();
            $table->text('detail_indicators')->nullable();
            $table->text('meta_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->text('specs')->nullable();
            $table->text('key_features')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_details');
    }
}; 