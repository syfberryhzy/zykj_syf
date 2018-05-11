<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->default('游客');
            $table->string('nickname')->comment('微信昵称');
            $table->string('avatar')->comment('头像')->default('http://www.gravatar.com/avatar');
            $table->string('phone')->comment('电话')->nullable();
            $table->string('openid')->comment('微信授权');
            $table->decimal('spend_total', 10, 2)->comment('个人总消费')->default('0.00');
            $table->decimal('spend_current', 10, 2)->comment('当月消费')->default('0.00');
            $table->decimal('victory_total', 10, 2)->comment('个人总业绩')->default('0.00');
            $table->decimal('victory_current', 10, 2)->comment('当月业绩')->default('0.00');
            $table->decimal('integral_total', 10, 2)->comment('个人总积分')->default('0.00');
            $table->decimal('integral_current', 10, 2)->comment('当前积分')->default('0.00');
            $table->decimal('m_total', 10, 2)->comment('个人总M币')->default('0.00');
            $table->decimal('m_current', 10, 2)->comment('当前M币')->default('0.00');
            $table->tinyInteger('status')->comment('身份 0=游客，1=申请会员， 2=会员')->default(0);
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
        Schema::dropIfExists('users');
    }
}
