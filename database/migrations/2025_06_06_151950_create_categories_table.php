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
        Schema::create('categories', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('level');
            $table->string('slug');
            $table->string('title');
            $table->string('image')->nullable();
            $table->string('parent_code')->nullable();
            $table->timestamps();

            $table->foreign('parent_code')->references('code')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
