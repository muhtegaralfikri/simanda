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
        Schema::create('funding_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('category', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('stage', 30); // planning, execution, financial
            $table->boolean('is_required')->default(false);
            $table->string('allowed_extensions', 255)->default('pdf,jpg,png,doc,docx');
            $table->integer('maximum_size')->default(5120); // in KB
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_year_id')->constrained('budget_years')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('program_code', 50);
            $table->string('program_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_year_id')->constrained('budget_years')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('person_in_charge_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('funding_source_id')->constrained('funding_sources')->cascadeOnDelete();
            $table->string('activity_code', 50);
            $table->string('activity_name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('target')->nullable();
            $table->bigInteger('budget_ceiling')->default(0);
            $table->integer('progress_percentage')->default(0);
            $table->string('status', 30)->default('draft'); // draft, planned, ongoing, waiting_verification, revision, completed, cancelled
            $table->string('submission_status', 30)->nullable(); // draft, submitted, verified, revision, rejected
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['budget_year_id', 'unit_id']);
            $table->index('status');
        });

        Schema::create('budget_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('expense_type_id')->constrained('expense_types')->cascadeOnDelete();
            $table->string('account_code', 50)->nullable();
            $table->string('description');
            $table->integer('volume');
            $table->string('unit', 50);
            $table->bigInteger('unit_price');
            $table->bigInteger('total');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('budget_plan_id')->nullable()->constrained('budget_plans')->nullOnDelete();
            $table->foreignId('expense_type_id')->constrained('expense_types')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('receipt_number', 100);
            $table->string('recipient_name')->nullable();
            $table->string('vendor_name')->nullable();
            $table->bigInteger('gross_amount');
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('net_amount');
            $table->string('payment_method', 50)->default('transfer');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft'); // draft, submitted, verified, revision, rejected
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'status']);
        });

        Schema::create('activity_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('file_path');
            $table->integer('file_size');
            $table->string('mime_type', 100);
            $table->string('status', 30)->default('uploaded'); // not_uploaded, uploaded, under_review, revision, valid, rejected
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'status']);
        });

        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verifier_id')->constrained('users')->cascadeOnDelete();
            $table->string('verifiable_type');
            $table->bigInteger('verifiable_id');
            $table->string('decision', 30); // approved, revision, rejected
            $table->text('notes')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();

            $table->index(['verifiable_type', 'verifiable_id']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('module', 50);
            $table->string('subject_type')->nullable();
            $table->bigInteger('subject_id')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'module']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('verifications');
        Schema::dropIfExists('activity_documents');
        Schema::dropIfExists('realizations');
        Schema::dropIfExists('budget_plans');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('expense_types');
        Schema::dropIfExists('funding_sources');
    }
};
