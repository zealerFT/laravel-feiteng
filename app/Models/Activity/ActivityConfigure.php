<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Model;

class ActivityConfigure extends Model
{

    protected $connection = 'mysql_activity';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'core_conf';

    protected $timestamp = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'p_activity_code', 'c_activity_code', 'activity_name', 'activity_data', 'start_time', 'end_time', 'created_at','updated_at'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

}