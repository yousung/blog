<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostTagTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('post_tag', function (Blueprint $table) {
            $table->unsignedInteger('post_id')->index()->comment('post_id');
            $table->unsignedInteger('tag_id')->index()->comment('post_id');

            $table->timestamps();
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('post_tag');
    }
}
