<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE trips
            MODIFY COLUMN status ENUM(
                'pending',
                'accepted',
                'arrived_pickup',
                'in_progress',
                'completed',
                'paid',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE trips
            MODIFY COLUMN status ENUM(
                'pending',
                'accepted',
                'arrived_pickup',
                'in_progress',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");
    }
};