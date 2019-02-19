<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'permission', 
        'password',
        'role_id',
        'picture',
        'expertise'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'created_at', 'updated_at', 'pivot', 'role_id'
    ];

    /**
     * The timelogs that belong to the user.
     */
     public function timelogs()
     {
         return $this->hasMany('App\Timelog');
     }

    /**
     * The projects that belong to the user.
     */
     public function projects()
     {
         return $this->belongsToMany('App\Project');
     }

    /**
     * Get the user role.
     */
     public function role()
     {
         return $this->belongsTo('App\Role');
     }

    /**
     * The projects that belong to the user.
     */
     public function news()
     {
         return $this->hasMany('App\News');
     }

     /**
      * Passport auth
      */
     public function findForPassport($username) {
        return self::where('username', $username)->first(); // change column name whatever you use in credentials
     }
}
