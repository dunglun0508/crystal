<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BlogsController;
use App\Http\Controllers\AboutUsController;
use App\Http\Controllers\BookingController;

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
Route::get('/', [HomeController::class, 'dashboard'])->name('home.dashboard');

//service 
Route::get('/blogs-news/{blog}', [BlogsController::class, 'detail'])->name('blogs.detail');
Route::get('/blogs-news', [BlogsController::class, 'index'])->name('blogs.index');
Route::get('/about-us', [AboutUsController::class, 'index'])->name('about-us.index');
Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
Route::get('/list-service', [ServiceController::class, 'index'])->name('service.index');
Route::get('/{category}/{code}', [ServiceController::class, 'detail'])->name('service.detail');
