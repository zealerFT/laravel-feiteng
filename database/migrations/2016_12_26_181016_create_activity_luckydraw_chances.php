<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLuckydrawChances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_activity')->create('luckydraw_chances', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0)->commemt('邀请人id');
            $table->string('p_activity_code',20)->default('')->commemt('母活动code，可能会包含多个子活动');
            $table->string('c_activity_code',20)->default('')->commemt('子活动唯一code');
            $table->integer('luckydraw_chances')->default(0)->commemt('抽奖机会');
            $table->integer('other_chances')->default(0)->commemt('赠送抽奖机会');
            $table->timestamp('other_chances_last_time')->comment('赠送抽奖机会的最后时间');
            $table->index(['p_activity_code', 'c_activity_code']);
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
        Schema::connection('mysql_activity')->drop('luckydraw_chances');
    }
}