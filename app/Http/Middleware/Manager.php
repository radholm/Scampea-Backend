<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\User;
use App\Project;

class Manager {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!Auth::user()->permission && is_null(Project::where('project_manager_id', Auth::user()->id)->where('id', $request->project_id)->first())) {

            return response([
                'message' => 'Forbidden, you must be a project manager or an admin'
            ], 403);

        }

        return $next($request);
    }
}
