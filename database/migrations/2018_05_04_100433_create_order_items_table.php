<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('product_id');
            $table->string('title')->comment('商品名称');
            $table->string('norm')->comment('规格参数');
            $table->integer('num')->comment('数量')->default(1);
            $table->decimal('pre_price', 10, 2)->comment('单价')->default('0.00');
            $table->decimal('total_price', 10, 2)->comment('总价')->default('0.00');
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
        Schema::dropIfExists('order_items');
    }
}
