<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Model;

class SpringFestivalOne extends Model
{

    protected $connection = 'mysql_activity';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'spring_festival_one';

    protected $timestamp = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'user_id', 'created_at','updated_at'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

}
