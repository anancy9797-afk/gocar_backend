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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('image')->nullable();
            $table->string('vehicle_number')->nullable(); // رقم لوحة المركبة أو تفاصيلها
            $table->string('vehicle_image')->nullable(); // رقم لوحة المركبة أو تفاصيلها
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};