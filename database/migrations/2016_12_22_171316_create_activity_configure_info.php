<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityConfigureInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_activity')->create('core_conf', function (Blueprint $table) {
            $table->increments('id');
            $table->string('p_activity_code',20)->default('')->commemt('母活动code，可能会包含多个子活动');
            $table->string('c_activity_code',20)->default('')->commemt('子活动唯一code');
            $table->string('activity_name',255)->default('')->commemt('活动名称');
            $table->text('activity_data')->default('')->commemt('活动自定义数据，JSON格式');
            $table->timestamp('start_time')->comment('活动开始时间');
            $table->timestamp('end_time')->comment('活动结束时间');
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
        Schema::connection('mysql_activity')->drop('core_conf');
    }
}
