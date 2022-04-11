<?php

use App\Models\User;
use App\Notifications\InvoicePaid;
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
    User::find(1)->notify(new InvoicePaid);
    return view('welcome');
});

Route::get('/create', function () {
    return view('create');
});
