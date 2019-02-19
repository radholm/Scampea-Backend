<?php

namespace App\Http\Controllers;

use App\Project;
use App\User;
use App\ProjectUser;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
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

    /**
     * Get users in this project
     *
     * @param [type] $project_id
     * @return void
     */
    public function users($project_id)
    {
        return Project::find($project_id)->users;
    }

    /**
     * Get the user projects
     *
     * @return void
     */
    public function getUserProjects()
    {
        return User::find(Auth::user()->id)->projects;
    }

    /**
     * Get the projects
     *
     * @return void
     */
    public function getProjects()
    {
        return Project::All();
    }

    /**
     * Gets a project with the specified ID
     *
     * @param [type] $name
     */
    public function getProject($project_id) {
        return Project::find($project_id);
    }

    /**
     * Creates a project
     *
     * @param [type] $name
     * @return void
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3|max:192',
            'project_manager_id' => 'required|numeric|exists:users,id',
        ]);

        return Project::create($request->input());
    }

    /**
     * Add a user to a project as admin
     *
     * @return void
     */
    public function addUser($project_id, $user_id){

        $input = [
            'user_id'       => $user_id,
            'project_id'    => $project_id,
        ];

        $validator = Validator::make($input, [
            'user_id'       => 'required|exists:users,id|unique:project_user,user_id,NULL,id,project_id,' . $project_id,
            'project_id'    => 'required|exists:projects,id'
        ], [
            'user_id.unique' => 'The user is already in that project.'
        ]);

        if($validator->fails()) {
            return $validator->errors();
        }

        return ProjectUser::create($input);
    }

    public function removeUser($project_id, $user_id)
    {
        $validator = Validator::make(['project_id' => $project_id, 'user_id' => $user_id], [
            'user_id' => [
                'required',
                Rule::exists('project_user')->where(function ($query) use ($user_id, $project_id) {
                    $query->where('user_id', $user_id);
                    $query->where('project_id', $project_id);
                }),
            ],
            'project_id' => 'required'
        ], [
            'user_id.exists' => 'The user is not in that project.'
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return [
            'success' => ProjectUser::where('user_id', $user_id)
                ->where('project_id', $project_id)
                ->delete()
        ];
    }

    /**
     * Method to delete a project
     *
     * @return void
     */
    public function deleteProject($id) {

        $validator = Validator::make(['id' => $id], [
            'id' => 'required|numeric|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return ['success' => Project::where('id', $id)->delete()];

    }

    /**
     * Method to update a projectname
     *
     * @return void
     */
    public function update(Request $request, $id) {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|numeric|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $request->validate([
            'name' => 'required|min:3|max:192',
        ]);

        return ['success' => Project::where('id', $id)->update($request->input())];
    }
}
