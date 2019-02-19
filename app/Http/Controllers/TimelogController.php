<?php

namespace App\Http\Controllers;

use App\User;
use App\Timelog;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TimelogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get all timelogs as admin
     *
     * @return void
     */
    public function getAllTimelogs()
    {
        return Timelog::All();
    }

    /**
     * Get user specific timelogs as admin
     *
     * @return void
     */
    public function getUserTimelogs($id) {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return Timelog::where('user_id', $id)->get();
    }

    /**
     * Get all timelogs for logged in user
     *
     * @return void
     */
    public function getTimelogs() {
        return Timelog::where('user_id', Auth::user()->id)->get();
    }

    /**
     * Create a new timelog
     *
     * @return void
     */
    public function create(Request $request) {
        $request->validate([
            'date'          => 'required|date_format:"Y-m-d"',
            'time'          => 'required|date_format:"H:i"',
            'contribution'  => 'required|min:2',
            'project_id'    => 'required|numeric|exists:projects,id',
        ]);

        return Timelog::create([
            'user_id'       => Auth::user()->id,
            'project_id'    => $request->project_id,
            'date'          => $request->date,
            'time'          => $request->time,
            'contribution'  => $request->contribution,
            'project'       => ''
        ]);
    }
    

    /**
     * Delete a timelog
     *
     * @return void
     */
    public function delete($id) {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|numeric|exists:timelogs,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $user_id = Auth::user()->id;

        $validator = Validator::make(['user_id' => $user_id, 'id' => $id], [
            'user_id' => [
                'required',
                Rule::exists('timelogs')->where(function ($query) use ($user_id, $id) {
                    $query->where('user_id', $user_id);
                    $query->where('id', $id);
                }),
            ]
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return ['success' => Timelog::where('id', $id)->delete()];
    }

    /**
     * Delete a timelog as admin
     *
     * @return void
     */
    public function deleteAsAdmin($id) {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|numeric|exists:timelogs,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return ['success' => Timelog::where('id', $id)->delete()];

    }

    /**
     * Update a timelog
     *
     * @return void
     */
    public function update(Request $request, $id) {
        $user_id = Auth::user()->id;

        $validator = Validator::make(['id' => $id, 'user_id' => $user_id], [
            'id' => 'required|numeric|exists:timelogs,id',
            'user_id' => [
                'required',
                Rule::exists('timelogs')->where(function ($query) use ($user_id, $id) {
                    $query->where('user_id', $user_id);
                    $query->where('id', $id);
                }),
            ]
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $request->validate([
            'date'          => 'date_format:"Y-m-d"',
            'time'          => 'date_format:"H:i"',
            'contribution'  => 'min:2',
        ]);

        return ['success' => Timelog::where('id', $id)->update($request->input())];

    }

    /**
     * Update a timelog as admin
     *
     * @return void
     */
    public function updateAsAdmin(Request $request, $id) {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|numeric|exists:timelogs,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $request->validate([
            'date'          => 'date_format:"Y-m-d"',
            'time'          => 'date_format:"H:i"',
            'contribution'  => 'min:2',
        ]);

        return ['success' => Timelog::where('id', $id)->update($request->input())];
    }

    /**
     * Get timelogs for a project as admin or project manager
     *
     * @return void
     */
    public function projectTimelogs(Request $request, $project_id) {
        $validator = Validator::make(['id' => $project_id], [
            'id' => 'required|numeric|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return Timelog::where('project_id', $project_id)->get();
    }
}
