<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('updated_at');
        });

        Schema::create('review_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('old_rating')->nullable();
            $table->text('old_comment')->nullable();
            $table->string('old_sentiment')->nullable();
            $table->json('old_criteria_ratings')->nullable();
            $table->timestamp('edited_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_revisions');
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['edited_at']);
        });
    }
};
