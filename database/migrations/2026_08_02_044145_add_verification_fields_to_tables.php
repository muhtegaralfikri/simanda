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
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->integer('verification_round')->default(1)->after('submitted_by');
            $table->timestamp('review_started_at')->nullable()->after('verification_round');
            $table->foreignId('review_started_by')->nullable()->after('review_started_at')->constrained('users')->nullOnDelete();
            $table->text('remaining_budget_note')->nullable()->after('cancellation_reason');
            $table->text('closing_note')->nullable()->after('remaining_budget_note');
            $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('verifications', function (Blueprint $table) {
            $table->integer('round')->default(1)->after('notes');
            $table->string('previous_status', 50)->nullable()->after('round');
            $table->string('new_status', 50)->nullable()->after('previous_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropColumn(['round', 'previous_status', 'new_status']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['review_started_by']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn([
                'submitted_by',
                'verification_round',
                'review_started_at',
                'review_started_by',
                'remaining_budget_note',
                'closing_note',
                'completed_by',
            ]);
        });
    }
};
