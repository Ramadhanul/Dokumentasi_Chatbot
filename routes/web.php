<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ChatbotController;

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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


Route::get('/', function () {
    return redirect()->route('documents.index');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('documents', DocumentController::class)->except(['edit','update']);
});
require __DIR__.'/auth.php'; // Breeze auth routes

Route::middleware('auth')->group(function () {
    Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');    // GET: tampilkan UI chat
    Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->name('chatbot.ask');    // POST: terima pertanyaan
});

Route::post('/chatbot/reset', function() {
    session()->forget('chat_history');
    return response()->json(['success' => true]);
})->middleware('auth')->name('chatbot.reset');


