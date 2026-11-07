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
        Schema::table('shipments', function (Blueprint $table) {
            // How the price was made up when it was quoted, for the receipt.
            $table->unsignedInteger('base_fee_cents')->nullable()->after('price_cents');
            $table->unsignedInteger('weight_charge_cents')->nullable()->after('base_fee_cents');
            $table->unsignedInteger('fuel_surcharge_cents')->nullable()->after('weight_charge_cents');
            $table->decimal('chargeable_weight_lb', 8, 2)->nullable()->after('fuel_surcharge_cents');

            // Payment: online by card (Stripe) or to the courier at pickup.
            $table->string('payment_status')->default('unpaid')->index()->after('chargeable_weight_lb');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'base_fee_cents', 'weight_charge_cents', 'fuel_surcharge_cents', 'chargeable_weight_lb',
                'payment_status', 'payment_method', 'payment_reference', 'paid_at',
            ]);
        });
    }
};
