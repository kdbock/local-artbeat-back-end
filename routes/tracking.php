<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingController;

// Tracking pixel for opens
Route::get('/newsletter/open/{recipient}', [TrackingController::class, 'open'])->name('newsletter.open');
// Tracking link for clicks
Route::get('/newsletter/click/{recipient}', [TrackingController::class, 'click'])->name('newsletter.click');
// Unsubscribe link
Route::get('/newsletter/unsubscribe/{recipient}', [TrackingController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
