<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type');               // solar_panel | battery | wind | inverter | other
            $table->unsignedInteger('power_watts')->nullable();
            $table->unsignedInteger('capacity_wh')->nullable();
            $table->decimal('price_per_day', 8, 2);
            $table->decimal('deposit', 8, 2)->default(0);
            $table->string('condition')->default('bon');
            $table->string('city');
            $table->string('status')->default('pending'); // pending | available | rented | maintenance | rejected
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
