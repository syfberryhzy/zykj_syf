<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('par_value', 10, 2)->comment('面值|单位:元')->default('0.00');
            $table->decimal('more_value', 10, 2)->comment('满多少可用|单位:元')->default('0.00');
            $table->string('title')->comment('描述');
            $table->Integer('quantum')->comment('限量人数')->default(1);
            $table->Integer('receive')->comment('领取人数')->default(0);
            $table->timestamp('start_at')->comment('开始时间');
            $table->timestamp('end_at')->comment('结束时间');
            $table->tinyInteger('status')->comment('状态 0=未发布,1=发布,2=已过期')->default(0);
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
        Schema::dropIfExists('coupons');
    }
}
