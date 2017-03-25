<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityCorePrizes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_activity')->create('core_prizes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('p_activity_code',20)->default('')->commemt('母活动code，可能会包含多个子活动');
            $table->string('c_activity_code',20)->default('')->commemt('子活动唯一code');
            $table->integer('position')->default(0)->commemt('抽奖活动奖品在页面上的位置');
            $table->string('type',20)->default('')->commemt('奖品类型：优惠券coupon、现金cash');
            $table->float('value')->default(0)->commemt('优惠券指券id，现金指金额');
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
        Schema::connection('mysql_activity')->drop('core_prizes');
    }
}