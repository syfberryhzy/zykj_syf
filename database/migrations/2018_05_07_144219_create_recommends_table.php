<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecommendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recommends', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->Integer('parent_id')->comment('推荐用户ID')->default(0);
            $table->string('member')->comment('下级会员ID 以逗号","隔开')->nullable();
            $table->string('visitor')->comment('下级游客ID 以逗号","隔开')->nullable();
            $table->integer('recommend')->comment('推荐会员人数')->default(0);
            $table->integer('visit')->comment('引导游客人数')->default(0);
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
        Schema::dropIfExists('recommends');
    }
}
