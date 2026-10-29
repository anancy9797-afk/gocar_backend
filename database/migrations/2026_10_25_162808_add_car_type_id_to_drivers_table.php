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
        Schema::table('drivers', function (Blueprint $table) {
            // إضافة حقل car_type_id بعد حقل رقم المركبة vehicle_number
            $table->unsignedBigInteger('car_type_id')->nullable()->after('vehicle_number');

            // (اختياري) إذا كنت تريد ربط الحقل كـ Foreign Key بجدول vehicle_types:
            // $table->foreign('car_type_id')->references('id')->on('vehicle_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('car_type_id');
        });
    }
};