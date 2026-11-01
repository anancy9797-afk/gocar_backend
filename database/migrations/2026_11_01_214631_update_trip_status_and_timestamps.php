
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });

        // Update enum values using a database statement
        \Illuminate\Support\Facades\DB::statement("
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

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE trips
            MODIFY COLUMN status ENUM(
                'pending',
                'accepted',
                'arrived',
                'in_progress',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");

        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'accepted_at',
                'arrived_at',
                'cancelled_at',
            ]);
        });
    }
};
