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
            $table->timestamp('started_at')->nullable()->after('status');
            $table->text('progress_note')->nullable()->after('progress_percentage');
            $table->timestamp('progress_updated_at')->nullable()->after('progress_note');
        });

        Schema::create('activity_progress_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->integer('progress_percentage');
            $table->text('note')->nullable();
            $table->foreignId('updated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('realizations', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('status');
        });

        Schema::table('activity_documents', function (Blueprint $table) {
            $table->foreignId('realization_id')->nullable()->after('activity_id')->constrained('realizations')->nullOnDelete();
            $table->integer('version')->default(1)->after('mime_type');
            $table->boolean('is_current')->default(true)->after('version');
            $table->timestamp('uploaded_at')->nullable()->after('is_current');
            $table->foreignId('updated_by')->nullable()->after('uploaded_by')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_documents', function (Blueprint $table) {
            $table->dropForeign(['realization_id']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['realization_id', 'version', 'is_current', 'uploaded_at', 'updated_by']);
        });

        Schema::table('realizations', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['updated_by', 'submitted_at']);
        });

        Schema::dropIfExists('activity_progress_logs');

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'progress_note', 'progress_updated_at']);
        });
    }
};
