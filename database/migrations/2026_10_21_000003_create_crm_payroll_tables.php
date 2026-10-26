<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_employees')) {
            Schema::create('crm_employees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->string('employee_code', 40)->nullable();
                $table->string('name');
                $table->string('designation', 120)->nullable();
                $table->string('department', 80)->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('nationality', 60)->nullable();
                $table->date('joining_date')->nullable();
                $table->string('salary_type', 10)->default('monthly');        // monthly | daily
                $table->decimal('basic_salary', 12, 2)->default(0);           // monthly basic, or the daily rate
                $table->decimal('housing_allowance', 12, 2)->default(0);
                $table->decimal('transport_allowance', 12, 2)->default(0);
                $table->decimal('food_allowance', 12, 2)->default(0);
                $table->decimal('other_allowance', 12, 2)->default(0);
                $table->decimal('overtime_rate', 10, 2)->default(0);          // per hour
                $table->string('payment_method', 30)->default('Cash');        // Cash | Bank Transfer
                $table->string('bank_name', 120)->nullable();
                $table->string('iban', 60)->nullable();
                $table->string('emirates_id', 40)->nullable();
                $table->date('emirates_id_expiry')->nullable();
                $table->string('passport_no', 40)->nullable();
                $table->date('visa_expiry')->nullable();
                $table->string('status', 10)->default('active')->index();     // active | left
                $table->date('left_date')->nullable();
                $table->string('notes', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('crm_employee_advances')) {
            Schema::create('crm_employee_advances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('employee_id')->index();
                $table->date('given_date');
                $table->decimal('amount', 12, 2);
                $table->decimal('installment', 12, 2)->nullable();          // recovered per month; empty = all at once
                $table->string('method', 30)->nullable();
                $table->string('note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('crm_payroll_runs')) {
            Schema::create('crm_payroll_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->string('period', 7);                                 // YYYY-MM
                $table->string('status', 10)->default('draft');              // draft | approved | paid
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('notes', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->unique(['workspace_id', 'period']);
            });
        }
        if (!Schema::hasTable('crm_payroll_lines')) {
            Schema::create('crm_payroll_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payroll_run_id')->index();
                $table->unsignedBigInteger('employee_id')->index();
                $table->string('employee_name');                             // snapshots for the payslip
                $table->string('designation', 120)->nullable();
                $table->string('department', 80)->nullable();
                $table->string('salary_type', 10);
                $table->decimal('basic_rate', 12, 2);                         // monthly basic, or the daily rate
                $table->decimal('allowances', 12, 2)->default(0);
                $table->decimal('days_worked', 6, 2)->default(0);             // daily wage only
                $table->decimal('absent_days', 6, 2)->default(0);             // monthly only
                $table->decimal('overtime_hours', 8, 2)->default(0);
                $table->decimal('overtime_rate', 10, 2)->default(0);
                $table->decimal('bonus', 12, 2)->default(0);
                $table->decimal('other_deductions', 12, 2)->default(0);
                $table->decimal('advance_deduction', 12, 2)->default(0);
                $table->decimal('gross', 12, 2)->default(0);
                $table->decimal('absence_deduction', 12, 2)->default(0);
                $table->decimal('net', 12, 2)->default(0);
                $table->string('payment_method', 30)->nullable();
                $table->string('note', 500)->nullable();
                $table->date('paid_at')->nullable();
                $table->string('paid_method', 30)->nullable();
                $table->string('paid_reference', 120)->nullable();
                $table->unsignedBigInteger('paid_by')->nullable();
                $table->timestamps();
                $table->unique(['payroll_run_id', 'employee_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_payroll_lines');
        Schema::dropIfExists('crm_payroll_runs');
        Schema::dropIfExists('crm_employee_advances');
        Schema::dropIfExists('crm_employees');
    }
};
