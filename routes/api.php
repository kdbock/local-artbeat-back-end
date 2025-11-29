<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;

Route::withoutMiddleware('Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful')->post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
Route::withoutMiddleware('Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful')->post('/register', [RegisteredUserController::class, 'store'])->name('register');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum')->name('logout');

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes for posts
Route::get('/posts', 'App\\Http\\Controllers\\PostController@index');
Route::get('/posts/{slug}', 'App\\Http\\Controllers\\PostController@show');

// Public routes for tours
Route::get('/tours', 'App\\Http\\Controllers\\TourController@index');
Route::get('/tours/{slug}', 'App\\Http\\Controllers\\TourController@show');

// Public routes for newsletter subscription
Route::post('/newsletter/subscribe', 'App\\Http\\Controllers\\NewsletterController@subscribe');
Route::post('/newsletter/confirm-email', 'App\\Http\\Controllers\\NewsletterController@confirmEmail');

// Admin/CMS routes for posts
Route::middleware(['auth:sanctum'])->withoutMiddleware('Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful')->group(function () {
    Route::post('/admin/posts', 'App\\Http\\Controllers\\PostController@store');
    Route::put('/admin/posts/{id}', 'App\\Http\\Controllers\\PostController@update');
    Route::delete('/admin/posts/{id}', 'App\\Http\\Controllers\\PostController@destroy');
});

// Admin/CMS routes for tours
Route::middleware(['auth:sanctum'])->withoutMiddleware('Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful')->group(function () {
    Route::post('/admin/tours', 'App\\Http\\Controllers\\TourController@store');
    Route::put('/admin/tours/{id}', 'App\\Http\\Controllers\\TourController@update');
    Route::delete('/admin/tours/{id}', 'App\\Http\\Controllers\\TourController@destroy');
});

// Admin/CMS routes for newsletter
Route::middleware(['auth:sanctum'])->withoutMiddleware('Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful')->group(function () {
    Route::get('/admin/newsletter-subscribers', 'App\\Http\\Controllers\\NewsletterController@index');
    Route::post('/admin/newsletter-subscribers', 'App\\Http\\Controllers\\NewsletterController@store');
    Route::post('/admin/newsletter-subscribers/import-csv', 'App\\Http\\Controllers\\NewsletterController@importCsv');
    Route::get('/admin/newsletter-subscribers/export-csv', 'App\\Http\\Controllers\\NewsletterController@exportCsv');
    Route::post('/admin/newsletter-subscribers/bulk-add-tag', 'App\\Http\\Controllers\\NewsletterController@bulkAddTag');
    Route::post('/admin/newsletter-subscribers/bulk-remove-tag', 'App\\Http\\Controllers\\NewsletterController@bulkRemoveTag');
    Route::post('/admin/newsletter-subscribers/bulk-change-status', 'App\\Http\\Controllers\\NewsletterController@bulkChangeStatus');
    Route::get('/admin/newsletter-subscribers/{id}', 'App\\Http\\Controllers\\NewsletterController@show');
    Route::put('/admin/newsletter-subscribers/{id}', 'App\\Http\\Controllers\\NewsletterController@update');
    Route::delete('/admin/newsletter-subscribers/{id}', 'App\\Http\\Controllers\\NewsletterController@destroy');
    
    Route::get('/admin/newsletter-tags', 'App\\Http\\Controllers\\NewsletterTagController@index');
    Route::post('/admin/newsletter-tags', 'App\\Http\\Controllers\\NewsletterTagController@store');
    Route::get('/admin/newsletter-tags/{id}', 'App\\Http\\Controllers\\NewsletterTagController@show');
    Route::put('/admin/newsletter-tags/{id}', 'App\\Http\\Controllers\\NewsletterTagController@update');
    Route::delete('/admin/newsletter-tags/{id}', 'App\\Http\\Controllers\\NewsletterTagController@destroy');
    Route::post('/admin/newsletter-tags/{id}/add-subscribers', 'App\\Http\\Controllers\\NewsletterTagController@addToSubscribers');
    Route::post('/admin/newsletter-tags/{id}/remove-subscribers', 'App\\Http\\Controllers\\NewsletterTagController@removeFromSubscribers');
    
    Route::get('/admin/newsletter-segments', 'App\\Http\\Controllers\\NewsletterSegmentController@index');
    Route::post('/admin/newsletter-segments', 'App\\Http\\Controllers\\NewsletterSegmentController@store');
    Route::get('/admin/newsletter-segments/{id}', 'App\\Http\\Controllers\\NewsletterSegmentController@show');
    Route::put('/admin/newsletter-segments/{id}', 'App\\Http\\Controllers\\NewsletterSegmentController@update');
    Route::delete('/admin/newsletter-segments/{id}', 'App\\Http\\Controllers\\NewsletterSegmentController@destroy');
    Route::post('/admin/newsletter-segments/{id}/recalculate', 'App\\Http\\Controllers\\NewsletterSegmentController@recalculate');
    Route::post('/admin/newsletter-segments/{id}/add-subscribers', 'App\\Http\\Controllers\\NewsletterSegmentController@addSubscribers');
    Route::post('/admin/newsletter-segments/{id}/remove-subscribers', 'App\\Http\\Controllers\\NewsletterSegmentController@removeSubscribers');
    
    Route::get('/admin/newsletter-campaigns', 'App\\Http\\Controllers\\CampaignController@index');
    Route::post('/admin/newsletter-campaigns', 'App\\Http\\Controllers\\CampaignController@store');
    Route::get('/admin/newsletter-campaigns/{id}', 'App\\Http\\Controllers\\CampaignController@show');
    Route::put('/admin/newsletter-campaigns/{id}', 'App\\Http\\Controllers\\CampaignController@update');
    Route::delete('/admin/newsletter-campaigns/{id}', 'App\\Http\\Controllers\\CampaignController@destroy');
    Route::post('/admin/newsletter-campaigns/{id}/send', 'App\\Http\\Controllers\\CampaignController@send');
    Route::post('/admin/newsletter-campaigns/{id}/send-test', 'App\\Http\\Controllers\\CampaignController@sendTest');
    Route::post('/admin/newsletter-campaigns/{id}/cancel', 'App\\Http\\Controllers\\CampaignController@cancel');
    Route::get('/admin/newsletter-campaigns/{id}/analytics', 'App\\Http\\Controllers\\CampaignController@getAnalytics');

    // RSS Feed Management
    Route::get('/admin/rss-feeds', [\App\Http\Controllers\RssFeedController::class, 'index']);
    Route::post('/admin/rss-feeds', [\App\Http\Controllers\RssFeedController::class, 'store']);
    Route::put('/admin/rss-feeds/{id}', [\App\Http\Controllers\RssFeedController::class, 'update']);
    Route::delete('/admin/rss-feeds/{id}', [\App\Http\Controllers\RssFeedController::class, 'destroy']);
    Route::get('/admin/rss-feeds/{id}/articles', [\App\Http\Controllers\RssFeedController::class, 'articles']);
    
    Route::get('/admin/email-templates', 'App\\Http\\Controllers\\EmailTemplateController@index');
    Route::post('/admin/email-templates', 'App\\Http\\Controllers\\EmailTemplateController@store');
    Route::get('/admin/email-templates/{emailTemplate}', 'App\\Http\\Controllers\\EmailTemplateController@show');
    Route::put('/admin/email-templates/{emailTemplate}', 'App\\Http\\Controllers\\EmailTemplateController@update');
    Route::delete('/admin/email-templates/{emailTemplate}', 'App\\Http\\Controllers\\EmailTemplateController@destroy');
    Route::post('/admin/email-templates/{emailTemplate}/clone', 'App\\Http\\Controllers\\EmailTemplateController@clone');
    Route::post('/admin/email-templates/{emailTemplate}/restore', 'App\\Http\\Controllers\\EmailTemplateController@restore');
});

// Admin/CMS routes for pages
Route::middleware(['auth:sanctum'])->withoutMiddleware('Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful')->group(function () {
    Route::post('/admin/pages', 'App\\Http\\Controllers\\PagesController@store');
    Route::get('/admin/pages', 'App\\Http\\Controllers\\PagesController@index');
    Route::get('/admin/pages/{id}', 'App\\Http\\Controllers\\PagesController@show');
    Route::put('/admin/pages/{id}', 'App\\Http\\Controllers\\PagesController@update');
    Route::delete('/admin/pages/{id}', 'App\\Http\\Controllers\\PagesController@destroy');
});

// Admin/CMS routes for donations
Route::middleware(['auth:sanctum'])->withoutMiddleware('Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful')->group(function () {
    Route::get('/admin/donations', 'App\\Http\\Controllers\\DonationController@index');
});

// Admin/CMS routes for site info
Route::middleware(['auth:sanctum'])->withoutMiddleware('Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful')->group(function () {
    Route::put('/admin/site-info', 'App\\Http\\Controllers\\SiteInfoController@update');
});
