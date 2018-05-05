<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            // $table->forenign('product_id')->references('id')->on('products');
            $table->string('norm', 50)->comment('规格参数');
            $table->decimal('unit_price', 10, 2)->comment('单价')->default('0.00');
            $table->Integer('quantity')->comment('库存')->default(0);
            $table->tinyInteger('status')->comment('状态 0=下架, 1=上架')->default(0);
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
        Schema::dropIfExists('product_items');
    }
}
