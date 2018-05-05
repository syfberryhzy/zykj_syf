<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->string('consignee')->comment('收件人');
            $table->string('phone')->comment('收件电话');
            $table->string('province')->comment('省');
            $table->string('city')->comment('市区');
            $table->string('district')->comment('县镇')->nullable();
            $table->string('address')->comment('详细地址');
            $table->string('zipcode')->comment('邮编')->nullable();
            $table->tinyInteger('status')->comment('状态 0=普通，1=默认')->default(0);
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
        Schema::dropIfExists('addresses');
    }
}
