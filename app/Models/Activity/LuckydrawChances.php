<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Model;

class LuckydrawChances extends Model
{

    protected $connection = 'mysql_activity';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'luckydraw_chances';

    protected $timestamp = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'user_id', 'p_activity_code', 'c_activity_code', 'luckydraw_chances', 'other_chances', 'other_chances_last_time', 'created_at', 'updated_at'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

}