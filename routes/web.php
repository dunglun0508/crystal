<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [HomeController::class, 'dashboard'])->name('home.dashboard');

//service 
Route::get('/list-service', [HomeController::class, 'list'])->name('service.list');
Route::get('{category}/{code}', [HomeController::class, 'index'])->name('service.index');
