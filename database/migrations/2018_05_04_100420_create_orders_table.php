<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('consignee')->comment('收件人');
            $table->string('phone')->comment('收件电话');
            $table->string('address')->comment('收件地址');
            $table->decimal('tradetotal', 10, 2)->comment('订单总金额')->default('0.00');
            $table->decimal('preferentialtotal', 10, 2)->comment('订单优惠金额')->default('0.00');
            $table->decimal('customerfreightfee', 10, 2)->comment('邮费')->default('0.00');
            $table->decimal('total', 10, 2)->comment('订单实付金额（实际应付）')->default('0.00');
            $table->decimal('paiedtotal', 10, 2)->comment('订单已付金额')->default('0.00');
            $table->string('freightbillno')->comment('物流单号')->nullable();
            $table->tinyInteger('status')->comment('订单状态 0=待付,1=失效,2=已付,3=发货,4=已收（待评),5=完成')->default(0);
            $table->timestamp('paiedtime')->comment('支付时间')->nullable();
            $table->tinyInteger('type')->comment('付款方式 0=微信支付，1=M币支付')->default(0);
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
        Schema::dropIfExists('orders');
    }
}
