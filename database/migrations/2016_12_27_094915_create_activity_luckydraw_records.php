<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLuckydrawRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_activity')->create('luckydraw_records', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0)->commemt('邀请人id');
            $table->string('p_activity_code',20)->default('')->commemt('母活动code，可能会包含多个子活动');
            $table->string('c_activity_code',20)->default('')->commemt('子活动唯一code');
            $table->integer('luckydraw_records')->default(0)->commemt('中奖纪录');
            $table->timestamp('records_time')->comment('中奖时间');
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
        Schema::connection('mysql_activity')->drop('luckydraw_records');
    }
}