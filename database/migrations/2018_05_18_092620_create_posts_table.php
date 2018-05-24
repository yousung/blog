<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('user_id')->index()->comment('유저 ID');
            $table->unsignedInteger('series_id')->nullable()->index()->comment('시리즈 ID');
            $table->integer('orderBy')->nullable()->comment('시리즈 순서');

            $table->string('title', 150)->comment('포스트 제목');
            $table->string('subTitle', 150)->comment('포스트 부제목');
            $table->text('context')->comment('포스트 내용');
            $table->integer('hit')->default(1)->comment('조회수');

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('series_id')->references('id')->on('series')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
