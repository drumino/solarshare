<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('reason')->default('scheduled_maintenance');
            $table->text('description')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('scheduled');
            $table->decimal('cost', 8, 2)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['equipment_id', 'starts_on', 'ends_on']);
            $table->index(['status', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
