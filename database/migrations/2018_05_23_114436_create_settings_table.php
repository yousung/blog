<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');

            $table->string('post_bg')->nullable()->comment('최신글 BG');
            $table->string('show_bg')->nullable()->comment('디테일 BG');
            $table->string('tag_bg')->nullable()->comment('카테고리 BG');
            $table->string('series_bg')->nullable()->comment('시리즈 BG');
            $table->string('subscribe_bg')->nullable()->comment('구독 BG');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
