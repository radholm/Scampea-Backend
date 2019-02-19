<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/users', 'UserController@users');
Route::get('/user', 'UserController@getUserInfo');
Route::get('/user/logout', 'UserController@logout');
Route::post('/user/create', 'UserController@create')->middleware('admin');
Route::delete('/user/delete/{id}', 'UserController@deleteUser')->middleware('admin');
Route::put('/user/changePassword', 'UserController@changePassword');
Route::put('/user/update/{id}', 'UserController@update')->middleware('admin');
Route::put('/user/update', 'UserController@updateUser');

Route::delete('/project/{project_id}', 'ProjectController@deleteProject')->middleware('admin');
Route::get('/projects', 'ProjectController@getProjects');
Route::get('/projects/{user_id}', 'ProjectController@getUserProjects');
Route::get('/project/{project_id}', 'ProjectController@getProject');
Route::get('/project/{project_id}/users', 'ProjectController@users');
Route::post('/project/create', 'ProjectController@create')->middleware('admin');
Route::post('/project/{project_id}/user/{user_id}', 'ProjectController@addUser')->middleware('manager');
Route::delete('/project/{project_id}/user/{user_id}', 'ProjectController@removeUser')->middleware('manager');
Route::put('/project/{project_id}', 'ProjectController@update')->middleware('admin');
Route::put('/projects/{project_id}/manager/{id}', 'ProjectController@updateManager')->middleware('admin');

Route::get('/timelogs/all', 'TimelogController@getAllTimelogs')->middleware('admin');
Route::get('/timelogs/{user_id}', 'TimelogController@getUserTimelogs')->middleware('admin');
Route::get('/timelogs/project/{project_id}', 'TimelogController@projectTimelogs')->middleware('manager');
Route::get('/timelogs', 'TimelogController@getTimelogs');
Route::post('/timelog', 'TimelogController@create');
Route::delete('/timelog/{id}', 'TimelogController@delete');
Route::delete('/timelog/{id}/admin', 'TimelogController@deleteAsAdmin')->middleware('admin');
Route::put('/timelog/{id}', 'TimelogController@update');
Route::put('/timelog/{id}/admin', 'TimelogController@updateAsAdmin')->middleware('admin');

Route::get('/roles', 'RoleController@getRoles')->middleware('admin');

Route::get('/news', 'NewsController@getUserNews');
Route::post('/news/create', 'NewsController@create')->middleware('admin');
