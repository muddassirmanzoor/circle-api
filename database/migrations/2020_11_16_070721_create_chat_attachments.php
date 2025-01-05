<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatAttachments extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable("chat_attachments")) {
            Schema::create('chat_attachments', function (Blueprint $table) {
                $table->bigIncrements('id');
                 $table->string('attachment_uuid', 255);
                 $table->string('attachment', 255);
                 $table->enum('attachment_type', ["audio", "video", "file","picture"])->nullable();
                 $table->integer('message_id');
                 $table->boolean('is_deleted')->default(false);
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
        Schema::dropIfExists('chat_attachments');
    }

}
