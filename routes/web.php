<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReadingLogEntryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/book', [BookController::class, 'index'])->name('book.index');
    Route::post('/book', [BookController::class, 'store'])->name('book.store');
    Route::post('/reading-log', [ReadingLogEntryController::class, 'store'])->name('reading.log.store');
    Route::delete('/reading-log/{readingLogEntry}', [ReadingLogEntryController::class, 'destroy'])->name('reading.log.destroy');
});

require __DIR__.'/settings.php';
