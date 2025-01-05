<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnTotalPaymentInFundsTransferTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('funds_transfers', function (Blueprint $table) {
            $table->double('total_payment',12,2)->default(0)->after('total_number_of_payments');
            $table->string('batch_no')->after('reference_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('funds_tansfers','total_payment'))
        {
            Schema::table('funds_transfer', function (Blueprint $table) {
                $table->dropColumn('total_payment');
                $table->dropColumn('batch_no');
            });
        }
    }
}
