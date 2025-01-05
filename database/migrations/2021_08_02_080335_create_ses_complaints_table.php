<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSesComplaintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ses_complaints', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ses_complaint_uuid')->unique();
            $table->string('type')->nullable()->default('complaint');
            $table->string('email_address')->unique();
            $table->string('message_id')->nullable();
            $table->string('feedback_id')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('source_email_address')->nullable();
            $table->string('source_arn')->nullable();
            $table->string('source_ip')->nullable();
            $table->timestamp('mail_time')->nullable();
            $table->string('sending_account_id')->nullable();
            $table->integer('is_archive')->nullable()->default(0);
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
        Schema::dropIfExists('ses_complaints');
    }
}
