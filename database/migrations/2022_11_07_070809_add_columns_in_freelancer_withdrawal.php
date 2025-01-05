<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInFreelancerWithdrawal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('freelancer_withdrawal', function (Blueprint $table) {
            
            $table->string('fp_account_title')->nullable()->after('invoice_id');
            $table->string('fp_account_number')->nullable()->after('fp_account_title');
            $table->string('fp_iban_account_number')->nullable()->after('fp_account_number');
            // $table->string('fp_account_currency')->nullable()->after('fp_iban_account_number');
            $table->string('fp_currency')->nullable()->after('fp_iban_account_number');
            $table->string('fp_currency_code')->nullable()->after('fp_currency');
            $table->timestamp('value_date')->nullable()->after('fp_currency_code');
            $table->string('fp_total_no_payments')->nullable()->after('value_date');
            $table->enum('fp_transaction_type',['LTR','FTR'])->default('LTR')->after('fp_total_no_payments');
            
            $table->string('fp_ordering_party_name')->nullable()->after('fp_transaction_type');
            $table->string('fp_ordering_party_address_1')->nullable()->after('fp_ordering_party_name');
            $table->string('fp_ordering_party_address_2')->nullable()->after('fp_ordering_party_address_1');
            $table->string('fp_ordering_party_address_3')->nullable()->after('fp_ordering_party_address_2');
            $table->string('fp_payment_reference')->nullable()->after('fp_ordering_party_address_3');




            $table->string('currency_code')->nullable()->after('currency');
            $table->string('swift_code')->nullable()->after('iban_account_number');

            $table->enum('transfer_status',['pending','in_progress','failed','transferred'])->after('schedule_status')->default('pending');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('freelancer_withdrawal', 'transfer_status')) {
            Schema::table('freelancer_earnings', function (Blueprint $table) {
                $table->dropColumn('transfer_status');
            });
        }
    }
}
