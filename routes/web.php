<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConsumerController;


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

Route::get('/consumers', [ConsumerController::class, 'index'])->name('consumers.index');
Route::get('/consumers/create', [ConsumerController::class, 'create'])->name('consumers.create');
Route::post('/consumers', [ConsumerController::class, 'store'])->name('consumers.store');
Route::get('/consumers/{id}/edit', [ConsumerController::class, 'edit'])->name('consumers.edit');
Route::put('/consumers/{id}', [ConsumerController::class, 'update'])->name('consumers.update');
Route::delete('/consumers/{id}', [ConsumerController::class, 'destroy'])->name('consumers.destroy');
Route::get('/consumers/search', [ConsumerController::class, 'search'])->name('consumers.search');