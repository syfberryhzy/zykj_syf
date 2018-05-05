<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->string('title')->comment('商品名称');
            $table->string('images')->comment('商品图片');
            $table->decimal('pre_price', 10, 2)->comment('商品基础价格')->default('0.00');
            $table->decimal('diff_price', 10, 2)->comment('商品差价（和会员价之差）')->default('0.00');
            $table->decimal('share_price', 10, 2)->comment('分享赚')->default('0.00');
            $table->Integer('sale_num')->comment('销量')->default(0);
            $table->string('description')->comment('简述');
            $table->text('conent')->comment('商品详情');
            $table->tinyInteger('type')->comment('商品版块类型 0=普通商品, 1=今日推荐,2=独家定制,3=限时秒杀,4=M币专区')->default(0);
            // $table->decimal('m_price', 2)->comment('M币价值')->default('0.00');
            $table->tinyInteger('status')->comment('状态 0=下架,1=在售')->default(0);
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
        Schema::dropIfExists('products');
    }
}
