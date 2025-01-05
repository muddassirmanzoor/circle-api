<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable("messages")) {
            Schema::create('messages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('message_uuid', 255);
                $table->string('content', 400);
                $table->string('channel', 255);
                $table->string('local_db_key', 255)->nullable();
                $table->string('receiver_uuid', false);
                $table->string('sender_uuid', false);
                $table->string('saved_timezone', 255);
                $table->string('local_timezone', 255);
                $table->string('deleted_one', 255)->nullable();
                $table->string('deleted_two', 255)->nullable();
                $table->enum('status', ["sent", "viewed", "delivered"])->default("sent");
                $table->enum('sender_type', ["freelancer", "customer", "admin"])->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('messages');
    }

}
