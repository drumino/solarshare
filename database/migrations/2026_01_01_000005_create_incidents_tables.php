<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('category')->default('other');  // hardware | battery | safety | damage | other
            $table->string('severity')->default('low');     // low | medium | high | critical
            $table->string('status')->default('open');      // open | in_progress | resolved
            $table->text('ai_summary')->nullable();
            $table->text('ai_advice')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->date('planned_at');
            $table->date('completed_at')->nullable();
            $table->decimal('cost', 8, 2)->default(0);
            $table->string('status')->default('planned');   // planned | in_progress | done
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tasks');
        Schema::dropIfExists('incidents');
    }
};
