<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\FcmController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\StatisticController;
use App\Http\Controllers\CalendarController;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

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


Route::post('/save-fcm-token', [FcmController::class, 'store'])
    ->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/rangkuman', [SummaryController::class, 'index'])
        ->name('rangkuman.index');
});

Route::get('/statistik', [StatisticController::class, 'index'])
    ->middleware('auth')
    ->name('statistik.index');

Route::post('/summary/{id}/regenerate', [SummaryController::class, 'regenerate'])
    ->name('summary.regenerate');

Route::middleware('auth')->group(function () {

    Route::get('/agenda', [CalendarController::class, 'index'])
        ->name('agenda.index');

    Route::post('/agenda/save', [CalendarController::class, 'save'])
        ->name('agenda.save');

    Route::get('/agenda/{date}', [CalendarController::class, 'show'])
        ->name('agenda.show');
});

Route::get('/health', function () {
    try {
        \DB::connection()->getPdo();
        return response()->json(['status' => 'ok']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error'], 500);
    }
});

Route::get('/metrics', function() {
    // Setup registry
    $registry = new CollectorRegistry(new InMemory());

    // Contoh metric: hit counter untuk homepage
    $counter = $registry->getOrRegisterCounter(
        'app',      // namespace
        'page_hits',// metric name
        'Jumlah hits per page', // description
        ['page']    // label
    );

    $counter->inc(['login']); // tambah 1 hit untuk halaman login, bisa diganti dinamis

    // Render metrics
    $renderer = new RenderTextFormat();
    $metrics = $renderer->render($registry->getMetricFamilySamples());

    return response($metrics, 200)
        ->header('Content-Type', RenderTextFormat::MIME_TYPE);
});
