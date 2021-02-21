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

Route::get('/', function () {
    return view('welcome');	
});


Route::get('/gmail/auth','App\Http\Controllers\GmailController@authorization');
Route::get('/mail','App\Http\Controllers\GmailController@callback');
Route::get('/inbox','App\Http\Controllers\GmailController@myInbox2')->name("inbox");
Route::get('/threads','App\Http\Controllers\GmailController@myInbox')->name("threads");
Route::get('/readonly-inbox','App\Http\Controllers\GmailController@threadJs')->name("threads");




Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
