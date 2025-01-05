<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundsTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('funds_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('funds_transfer_uuid');
            $table->string('reference_no')->nullable();
            $table->string('connect_id')->nullable();
            $table->string('file_customer_id')->nullable();
            // $table->string('total_record_count')->nullable();
            $table->string('file_authorization_type')->nullable();
            $table->string('total_number_of_payments')->nullable();
            $table->string('status')->default('pending')->nullable();
            $table->string('is_transfered')->default(0)->nullable();
            $table->string('is_archive')->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('funds_transfers');
    }
}
