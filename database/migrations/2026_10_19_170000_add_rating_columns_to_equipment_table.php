<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->unsignedInteger('reviews_count')->default(0)->after('image_path');
            $table->decimal('reviews_avg', 3, 2)->default(0)->after('reviews_count');
            $table->decimal('bayesian_score', 5, 3)->default(0)->after('reviews_avg');
            $table->index('bayesian_score', 'equipment_bayesian_score_idx');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex('equipment_bayesian_score_idx');
            $table->dropColumn(['reviews_count', 'reviews_avg', 'bayesian_score']);
        });
    }
};
