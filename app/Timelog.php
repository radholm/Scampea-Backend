<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Timelog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
     protected $fillable = [
        'user_id',
        'project_id',
        'date',
        'time',
        'contribution',
    ];

    /**
     * Get the author of the timelogs.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
