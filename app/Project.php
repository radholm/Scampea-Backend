<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use Notifiable;

    protected $fillable = [ 'name', 'project_manager_id' ];
    protected $hidden = [ 'pivot', ];

    /**
     * The users that belong to the project.
     */
     public function users()
     {
         return $this->belongsToMany('App\User');
     }
}
