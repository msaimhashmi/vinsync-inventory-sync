<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vin', 20)->unique();
            $table->string('stock_number', 50)->nullable();
            $table->string('condition', 10)->nullable()->index();   // 'new' | 'used'
            $table->string('make', 60)->nullable()->index();
            $table->string('model', 80)->nullable()->index();
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->string('trim', 100)->nullable();
            $table->string('body_style', 60)->nullable()->index();
            $table->string('engine', 120)->nullable();
            $table->string('transmission', 60)->nullable()->index();
            $table->string('drivetrain', 60)->nullable()->index();
            $table->string('fuel_type', 60)->nullable()->index();
            $table->unsignedSmallInteger('mpg_city')->nullable();
            $table->unsignedSmallInteger('mpg_hwy')->nullable();
            $table->decimal('msrp', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->unsignedInteger('mileage')->nullable()->index();
            $table->string('exterior_color', 100)->nullable()->index();
            $table->string('exterior_color_code', 10)->nullable();
            $table->string('interior_color', 160)->nullable();
            $table->string('interior_color_code', 10)->nullable();
            $table->string('status', 40)->nullable();
            $table->string('dealer_name', 100)->nullable();
            $table->string('dealer_city', 60)->nullable();
            $table->string('dealer_state', 10)->nullable();
            $table->text('detail_url')->nullable();
            $table->json('features')->nullable();
            $table->json('images')->nullable();
            $table->text('window_sticker_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
