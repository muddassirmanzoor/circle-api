<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMoyasarWebFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('moyasar_web_forms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('moyasar_web_form_uuid')->unique();
            $table->string('profile_uuid');
            $table->string('payment_id')->unique()->nullable();
            $table->double('amount');
            $table->string('currency');
            $table->text('description');
            $table->timestamp('expired_at');
            $table->string('status')->nullable()->default('pending');
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
        Schema::dropIfExists('moyasar_web_forms');
    }
}
