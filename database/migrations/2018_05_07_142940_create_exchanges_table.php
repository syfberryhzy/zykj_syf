<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExchangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchanges', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->decimal('total', 10, 2)->comment('总计')->default('0.00');
            $table->decimal('amount', 10, 2)->comment('兑换|付款')->default('0.00');
            $table->decimal('current', 10, 2)->comment('目前')->default('0.00');
            $table->Integer('model')->comment('来源模型 1=exchanges, 2=order')->default(1);
            $table->tinyInteger('status')->comment('1=积分 2=M币')->default(1);
            $table->tinyInteger('type')->comment('方式 1=增加 2=减少')->default(1);
            $table->Integer('uri')->comment('模型ID');
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
        Schema::dropIfExists('exchanges');
    }
}
