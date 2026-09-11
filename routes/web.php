<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\ReadingLogEntryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
    Route::get('/book', [BookController::class, 'index'])->name('book.index');
    Route::post('/book', [BookController::class, 'store'])->name('book.store');
    Route::post('/reading-log', [ReadingLogEntryController::class, 'store'])->name('reading.log.store');
});

require __DIR__.'/settings.php';
