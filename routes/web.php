<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RoomController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/room', [RoomController::class, 'index'])->name('room');
Route::post('/room', [RoomController::class, 'createRoom'])->name('room.create');
Route::put('/room', [RoomController::class, 'updateRoom'])->name('room.update');
Route::delete('/room/{id}', [RoomController::class, 'deleteRoom'])->name('room.delete');
Route::post('/room/join', [RoomController::class, 'joinRoom'])->name('room.join');
Route::delete('/room/withdraw/{id}', [RoomController::class, 'withdraw'])->name('room.withdraw');

Route::get('/chat', [ChatController::class, 'index'])->name('chat');
Route::post('/chat', [ChatController::class, 'create'])->name('chat.create');
Route::put('/chat/delete', [ChatController::class, 'deleteMessage'])->name('chat.delete');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
