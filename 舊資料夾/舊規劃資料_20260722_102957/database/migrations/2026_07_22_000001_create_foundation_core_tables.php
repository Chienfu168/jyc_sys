<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('name', 100);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 50)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('short_name', 80)->nullable();
            $table->string('type', 80)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website')->nullable();
            $table->unsignedInteger('student_count')->nullable();
            $table->unsignedInteger('class_count')->nullable();
            $table->string('category', 80)->nullable();
            $table->string('remote_status', 80)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 50)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('school_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('title', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('line_id', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('gender', 30)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address')->nullable();
            $table->text('education')->nullable();
            $table->text('experience')->nullable();
            $table->string('professional_field', 150)->nullable();
            $table->string('teaching_specialty', 150)->nullable();
            $table->text('introduction')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_branch', 100)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->string('account_name', 100)->nullable();
            $table->decimal('hourly_rate', 12, 2)->nullable();
            $table->decimal('session_rate', 12, 2)->nullable();
            $table->decimal('transportation_fee', 12, 2)->nullable();
            $table->text('special_rate_note')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('type', 80)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->text('cooperation_description')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('work_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title', 150);
            $table->text('summary')->nullable();
            $table->text('goals')->nullable();
            $table->string('status', 50)->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('work_plan_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('name', 180);
            $table->string('code', 80)->nullable();
            $table->string('category', 80);
            $table->string('manager_name', 100)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->decimal('budget_amount', 12, 2)->default(0);
            $table->decimal('actual_cost', 12, 2)->default(0);
            $table->unsignedInteger('participant_target')->nullable();
            $table->unsignedInteger('participant_actual')->nullable();
            $table->string('status', 50)->default('draft');
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('name', 180);
            $table->string('semester', 80)->nullable();
            $table->string('assistant_teacher', 100)->nullable();
            $table->unsignedInteger('total_weeks')->nullable();
            $table->decimal('hours_per_session', 5, 2)->nullable();
            $table->unsignedInteger('participant_count')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('description')->nullable();
            $table->text('teaching_goal')->nullable();
            $table->string('status', 50)->default('planning');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedInteger('session_number');
            $table->date('session_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->unsignedInteger('student_count')->nullable();
            $table->text('teaching_content')->nullable();
            $table->text('learning_status')->nullable();
            $table->text('teacher_note')->nullable();
            $table->text('administrative_note')->nullable();
            $table->string('status', 50)->default('scheduled');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('name', 180);
            $table->string('type', 80)->nullable();
            $table->date('activity_date')->nullable();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('location', 150)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('organizer', 150)->nullable();
            $table->string('co_organizer', 150)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->unsignedInteger('student_count')->default(0);
            $table->unsignedInteger('teacher_count')->default(0);
            $table->unsignedInteger('volunteer_count')->default(0);
            $table->unsignedInteger('guest_count')->default(0);
            $table->unsignedInteger('total_participants')->default(0);
            $table->decimal('budget_amount', 12, 2)->default(0);
            $table->decimal('actual_cost', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('status', 50)->default('planning');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('category', 100);
            $table->decimal('budget_amount', 12, 2)->default(0);
            $table->decimal('actual_amount', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->date('date');
            $table->string('type', 100);
            $table->decimal('amount', 12, 2);
            $table->string('payer', 150)->nullable();
            $table->string('payment_method', 80)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->date('date');
            $table->string('category', 100);
            $table->string('vendor', 150)->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 80)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->string('reimbursement_status', 50)->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->unsignedInteger('participant_count')->nullable();
            $table->text('result_summary')->nullable();
            $table->text('school_feedback')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->text('student_feedback')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('annual_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title', 180);
            $table->longText('work_summary')->nullable();
            $table->longText('financial_summary')->nullable();
            $table->longText('outcome_summary')->nullable();
            $table->string('status', 50)->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('annual_reports');
        Schema::dropIfExists('outcomes');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('course_sessions');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('work_plans');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('school_contacts');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('fiscal_years');
    }
};

