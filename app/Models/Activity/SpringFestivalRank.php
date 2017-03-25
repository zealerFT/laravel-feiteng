<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Model;

class SpringFestivalRank extends Model
{

    protected $connection = 'mysql_activity';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'spring_festival_rank';

    protected $timestamp = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'user_id', 'user_mobile','user_name', 'friend_id', 'friend_name', 'trade_id', 'fee', 'order_time', 'created_at', 'updated_at'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['user_id', 'order_time'];

}
