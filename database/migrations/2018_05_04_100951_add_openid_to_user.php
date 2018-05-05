<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOpenidToUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->comment('电话');
            $table->string('avatar')->comment('头像');
            $table->string('openid')->comment('微信授权');
            $table->decimal('spend_total', 10, 2)->comment('个人总消费')->default('0.00');
            $table->decimal('spend_current', 10, 2)->comment('当月消费')->default('0.00');
            $table->decimal('integral_total', 10, 2)->comment('个人总积分')->default('0.00');
            $table->decimal('integral_current', 10, 2)->comment('当前积分')->default('0.00');
            $table->decimal('m_total', 10, 2)->comment('个人总M币')->default('0.00');
            $table->decimal('m_current', 10, 2)->comment('当前M币')->default('0.00');
            $table->integer('recommend')->comment('推荐人数')->default(0);
            $table->tinyInteger('status')->comment('身份 0=游客，1=申请会员， 2=会员')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
