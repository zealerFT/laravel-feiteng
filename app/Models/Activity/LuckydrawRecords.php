<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Model;

class LuckydrawRecords extends Model
{

    protected $connection = 'mysql_activity';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'luckydraw_records';

    protected $timestamp = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'user_id', 'p_activity_code', 'c_activity_code', 'luckydraw_records', 'records_time', 'created_at', 'updated_at'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

}