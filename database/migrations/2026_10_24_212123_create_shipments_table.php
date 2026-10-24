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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number', 20)->unique();
            $table->string('status')->index();
            $table->string('service_level');
            $table->string('source')->default('admin');
            $table->string('sender_name');
            $table->string('sender_company')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('sender_phone');
            $table->string('sender_address');
            $table->string('sender_city');
            $table->string('sender_country', 2);
            $table->string('receiver_name');
            $table->string('receiver_company')->nullable();
            $table->string('receiver_email')->nullable();
            $table->string('receiver_phone');
            $table->string('receiver_address');
            $table->string('receiver_city');
            $table->string('receiver_country', 2);
            $table->string('contents');
            $table->unsignedSmallInteger('pieces')->default(1);
            $table->decimal('weight_kg', 8, 2);
            $table->unsignedSmallInteger('length_cm')->nullable();
            $table->unsignedSmallInteger('width_cm')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedBigInteger('declared_value_cents')->default(0);
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->date('pickup_date')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('received_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
