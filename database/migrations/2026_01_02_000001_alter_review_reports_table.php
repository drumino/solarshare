<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // migrate old status values before applying the new CHECK constraint
        \Illuminate\Support\Facades\DB::table('review_reports')
            ->where('status', 'open')->update(['status' => 'pending']);
        \Illuminate\Support\Facades\DB::table('review_reports')
            ->where('status', 'resolved')->update(['status' => 'reviewed']);

        Schema::table('review_reports', function (Blueprint $table) {
            $table->enum('status', ['pending', 'reviewed', 'dismissed'])->default('pending')->change();
            $table->enum('severity', ['low', 'medium', 'high'])->nullable()->after('status');
            $table->string('category')->nullable()->after('severity');
            $table->string('suggested_action')->nullable()->after('category');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('suggested_action');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        Schema::table('review_reports', function (Blueprint $table) {
            $table->unique(['user_id', 'review_id']);
        });
    }

    public function down(): void
    {
        Schema::table('review_reports', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'review_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['severity', 'category', 'suggested_action', 'reviewed_by', 'reviewed_at']);
            $table->string('status')->default('open')->change();
        });
    }
};
