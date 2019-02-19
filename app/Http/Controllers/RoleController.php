<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Role;

class RoleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
         $this->middleware('auth:api');
    }

    public function getRoles() {
        return Role::All();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function updateManager($project_id, $manager_id) {
        $validator = Validator::make(['uid' => $manager_id, 'pid' => $project_id], [
            'pid' => 'required|numeric|exists:users,id',
            'uid' => 'required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return ['success' => Project::where('id', $project_id)->update(['project_manager_id' => $manager_id])];
    }


}
