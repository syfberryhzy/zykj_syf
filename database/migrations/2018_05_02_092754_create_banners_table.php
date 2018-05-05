<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 50)->comment('标题')->nullable();
            $table->string('image')->comment('图片');
            $table->string('url')->comment('链接')->nullable();
            $table->integer('sort')->comment('排序')->default(0);
            $table->tinyInteger('status')->comment('banner状态 1=显示，0=隐藏')->default(1);
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
        Schema::dropIfExists('banners');
    }
}
