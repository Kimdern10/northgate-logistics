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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_driver')->default(false)->after('is_admin');
            $table->string('phone', 30)->nullable()->after('email');
        });

        Schema::table('shipments', function (Blueprint $table) {
            // The courier collecting or delivering it today.
            $table->foreignId('driver_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_driver', 'phone']);
        });
    }
};
