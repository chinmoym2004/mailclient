<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/','App\Http\Controllers\HomeController@index');
Route::post('/add-to-tracking','App\Http\Controllers\HomeController@AddToTracking');
Route::get('/authenticate','App\Http\Controllers\HomeController@getToken');

Route::get('/gmail/auth','App\Http\Controllers\GmailController@authorization');
Route::get('/mail','App\Http\Controllers\GmailController@callback');
Route::get('/inbox','App\Http\Controllers\GmailController@myInbox2')->name("inbox");
Route::get('/threads','App\Http\Controllers\GmailController@myInbox')->name("threads");
Route::get('/readonly-inbox','App\Http\Controllers\GmailController@threadJs')->name("threadsjs");
Route::get('/readonly-inbox/{thredId}','App\Http\Controllers\GmailController@singleThreadJs')->name("singlethreadsjs");

Route::get('/readonly-inbox/{thredId}/php','App\Http\Controllers\GmailController@singleThreadphp')->name("singlethreadsjsphp");

Route::post('/update-token','App\Http\Controllers\GmailController@updateToken')->name("updateusertoken");

Route::post("replymessage", 'App\Http\Controllers\GmailController@replyEmail')->name("replymessage");

Route::get("create/message", 'App\Http\Controllers\GmailController@createMessage')->name("createmessage");
Route::post("sendmessage", 'App\Http\Controllers\GmailController@sendEmail')->name("senddMessage");


Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
