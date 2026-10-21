<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->text('owner_reply')->nullable()->after('moderation_note');
            $table->timestamp('owner_reply_at')->nullable()->after('owner_reply');
            $table->foreignId('owner_reply_by')->nullable()->constrained('users')->nullOnDelete()->after('owner_reply_at');
            $table->timestamp('owner_reply_edited_at')->nullable()->after('owner_reply_by');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['owner_reply_by']);
            $table->dropColumn(['owner_reply', 'owner_reply_at', 'owner_reply_by', 'owner_reply_edited_at']);
        });
    }
};
