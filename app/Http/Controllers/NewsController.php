<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\User;
use App\News;

class NewsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getUserNews()
    {
        return User::find(Auth::user()->id)->news;
    }
    
    /**
     * Create a new news item
     *
     * @return void
     */
    public function create(Request $request) {
        $request->validate([
            'title'         => 'required|String',
            'text'          => 'required|String',
        ]);

        return User::all()->map(function($user) use ($request) {
            return News::create([
                'title'         => $request->title,
                'text'          => $request->text,
                'user_id'       => $user->id,
            ]);
        });
    }
}
