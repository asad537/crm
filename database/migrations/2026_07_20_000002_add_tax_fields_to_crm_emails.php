<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTaxFieldsToCrmEmails extends Migration
{
    public function up()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->string('customer_trn')->nullable()->after('client_phone');
            $table->string('company_trn')->nullable()->after('customer_trn');
            $table->string('trade_license_number')->nullable()->after('company_trn');
            $table->string('invoice_currency', 10)->default('USD')->after('payment_status');
            $table->decimal('vat_percentage', 5, 2)->default(0)->after('invoice_currency');
        });

        $alMassaWorkspaceId = DB::table('crm_workspaces')
            ->where('slug', 'mybox-packaging-app')
            ->value('id');

        if ($alMassaWorkspaceId) {
            DB::table('crm_emails')
                ->where('workspace_id', $alMassaWorkspaceId)
                ->update([
                    'invoice_currency' => 'AED',
                    'vat_percentage' => 5,
                    'trade_license_number' => '801625',
                ]);
        }
    }

    public function down()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->dropColumn([
                'customer_trn',
                'company_trn',
                'trade_license_number',
                'invoice_currency',
                'vat_percentage',
            ]);
        });
    }
}
