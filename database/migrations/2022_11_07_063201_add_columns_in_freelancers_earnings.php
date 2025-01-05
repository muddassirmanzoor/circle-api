<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInFreelancersEarnings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('freelancer_earnings', function (Blueprint $table) {
            $table->enum('transfer_status',['pending','in_progress','failed','transferred'])->after('currency')->default('pending');
            $table->bigInteger('funds_transfers_id')->nullable()->after('freelancer_withdrawal_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('freelancer_earnings', 'transfer_status')) {
            Schema::table('freelancer_earnings', function (Blueprint $table) {
                $table->dropColumn('transfer_status');
                $table->dropColumn('funds_transfers_id');
            });
        }
    }
}
