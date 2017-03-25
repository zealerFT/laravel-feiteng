<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpringFestivalRankTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_activity')->create('spring_festival_rank', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0)->commemt('邀请人id');
            $table->string('user_mobile',10)->default('')->commemt('邀请人手机号');
            $table->string('user_name',20)->default('')->commemt('邀请人姓名');
            $table->integer('friend_id')->default(0)->comment('被邀请人id');
            $table->string('friend_mobile',10)->default('')->commemt('被邀请人手机号');
            $table->string('friend_name',20)->default('')->commemt('被邀请人姓名');
            $table->integer('trade_id')->default(0)->comment('订单号');
            $table->double('fee',10)->default(0)->comment('订单金额');
            $table->timestamp('order_time')->comment('订单时间');
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
        Schema::connection('mysql_activity')->drop('spring_festival_rank');
    }
}
