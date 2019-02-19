<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Image;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
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
     * Get all users
     *
     * @return void
     */
    public function users() {
        return User::with('role')->get();
    }

    /**
     * Get info for the current user
     *
     * @return void
     */
    public function getUserInfo()
    {
        return Auth::user();
    }

    /**
     * Check if there is a valid image in the request and upload it
     *
     * @param Request $request
     * @return string
     */
    public function imageUpload(Request $request, $username) {
        if (!$request->has('picture')) return '';

        $imageData = $request->get('picture');
        $filename = $username . "." . explode('/', explode(':', substr($imageData, 0, strpos($imageData, ';')))[1])[1]; 

        $path = public_path('pictures/' . $filename);
        $db_path = '/pictures/' . $filename;

        if(\File::exists($path)) {
            \File::delete($path);
        }

        Image::make($imageData)->save($path);

        return $db_path;

    }

    /**
     * Create a new user
     *
     * @return void
     */
    public function create(Request $request) {
        $request->validate([
            'username'      => 'required|unique:users|min:3|max:32',
            'first_name'    => 'required|min:3|max:32',
            'last_name'     => 'required|min:3|max:32',
            'password'      => 'required|confirmed|min:3|max:32',
            'permission'    => 'boolean',
            'role'          => 'required|exists:roles,id',
        ]);

        $data = $request->input();

        return User::create([
            'username'      => $data['username'],
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'password'      => bcrypt($data['password']),
            'picture'       => $this->imageUpload($request, $data['username']),
            'permission'    => $request->input('permission', 0),
            'role_id'       => $data['role'],
            'expertise'     => ''
        ]);
    }

    /**
     * Method to delete user
     *
     * @return void
     */
    public function deleteUser($id)
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return ['success' => User::where('id', $id)->delete()];
    }

    /**
     * Method to change password
     *
     * @return void
     */
    public function changePassword(Request $request) {
        $request->validate([
            'old_password'      => 'required',
            'new_password'      => 'required|confirmed|min:3|max:32',
        ]);

        if(!Hash::check($request->old_password, Auth::user()->password)) {
            return \Response::json(['success' => false, 'error' => 'Password missmatch'], 400);
        }

        $request->user()->password = bcrypt($request->new_password);
        $request->user()->save();

        return ['success' => true];
    }

    /**
     * Method to logout
     *
     * @return void
     */
    public function logout() {
        Auth::user()->token()->revoke();

        return ['success' => true];
    }

    /**
     * Method to update a user as admin
     *
     * @return void
     */
    public function update(Request $request, $id) {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $request->validate([
            'username'      => 'unique:users|min:3|max:32',
            'first_name'    => 'min:3|max:32',
            'last_name'     => 'min:3|max:32',
            'password'      => 'confirmed|min:3|max:32',
            'permission'    => 'boolean',
            'role'          => 'exists:roles,id',
        ]);

        $new_password = $request->input('password', 'NULL');

        if($new_password !== 'NULL') {
            $request['password'] = bcrypt($new_password);
        }

        $username = $request->input('username', User::where('id', $id)->value('username'));
        $picture = $this->imageUpload($request, $username);
        
        if($picture !== '') {
            $request['picture'] = $picture;
        }

        return ['success' => User::where('id', $id)->update($request->except(['password_confirmation']))];
    }

    /**
     * Method to update a user as user
     *
     * @return void
     */
    public function updateUser(Request $request) {
        $picture = $this->imageUpload($request, Auth::user()->username);

        $user_id = Auth::user()->id;

        if(!($picture === '')) {
            $request['picture'] = $picture;
        }
        
        return ['success' => User::where('id', $user_id)->update($request->input())];
    }
}
