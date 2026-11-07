<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * US customers think in pounds and inches, so weights, box sizes and rates are stored that way.
 * Existing numbers are converted, not lost.
 */
return new class extends Migration
{
    private const float LB_PER_KG = 2.20462;

    private const float CM_PER_IN = 2.54;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->renameColumn('weight_kg', 'weight_lb');
            $table->renameColumn('length_cm', 'length_in');
            $table->renameColumn('width_cm', 'width_in');
            $table->renameColumn('height_cm', 'height_in');
        });

        Schema::table('rates', function (Blueprint $table) {
            $table->renameColumn('per_kg_cents', 'per_lb_cents');
        });

        DB::table('shipments')->update([
            'weight_lb' => DB::raw('ROUND(weight_lb * '.self::LB_PER_KG.', 2)'),
            'length_in' => DB::raw('ROUND(length_in / '.self::CM_PER_IN.')'),
            'width_in' => DB::raw('ROUND(width_in / '.self::CM_PER_IN.')'),
            'height_in' => DB::raw('ROUND(height_in / '.self::CM_PER_IN.')'),
        ]);

        DB::table('rates')->update(['per_lb_cents' => DB::raw('ROUND(per_lb_cents / '.self::LB_PER_KG.')')]);

        // 5000 cm³ per kg is the metric airline standard; 139 in³ per lb is the US one.
        DB::table('settings')->where('key', 'volumetric_divisor')->where('value', '5000')->update(['value' => '139']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('shipments')->update([
            'weight_lb' => DB::raw('ROUND(weight_lb / '.self::LB_PER_KG.', 2)'),
            'length_in' => DB::raw('ROUND(length_in * '.self::CM_PER_IN.')'),
            'width_in' => DB::raw('ROUND(width_in * '.self::CM_PER_IN.')'),
            'height_in' => DB::raw('ROUND(height_in * '.self::CM_PER_IN.')'),
        ]);

        DB::table('rates')->update(['per_lb_cents' => DB::raw('ROUND(per_lb_cents * '.self::LB_PER_KG.')')]);
        DB::table('settings')->where('key', 'volumetric_divisor')->where('value', '139')->update(['value' => '5000']);

        Schema::table('shipments', function (Blueprint $table) {
            $table->renameColumn('weight_lb', 'weight_kg');
            $table->renameColumn('length_in', 'length_cm');
            $table->renameColumn('width_in', 'width_cm');
            $table->renameColumn('height_in', 'height_cm');
        });

        Schema::table('rates', function (Blueprint $table) {
            $table->renameColumn('per_lb_cents', 'per_kg_cents');
        });
    }
};
