<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
          #一级分类
            $table->increments('id');
            $table->string('title', 50)->comment('分类标题');
            $table->string('icon')->comment('图标');
            $table->integer('parent_id')->comment('父级ID')->default(0);
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
        Schema::dropIfExists('categories');
    }
}
