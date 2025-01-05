<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSesBouncesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ses_bounces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ses_bounce_uuid')->unique();
            $table->string('type')->nullable()->default('bounce');
            $table->string('sub_type')->nullable();
            $table->string('email_address')->unique();
            $table->text('diagnostic_code')->nullable();
            $table->string('message_id')->nullable();
            $table->string('feedback_id')->nullable();
            $table->string('reporting_mta')->nullable();
            $table->string('remote_mta_ip')->nullable();
            $table->string('source_email_address')->nullable();
            $table->string('source_arn')->nullable();
            $table->string('source_ip')->nullable();
            $table->string('action')->nullable();
            $table->timestamp('mail_time')->nullable();
            $table->string('sending_account_id')->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('ses_bounces');
    }
}
