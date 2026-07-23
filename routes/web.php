<?php

use App\Models\AffiliateClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home-page')->name('home');
Route::livewire('/movies', 'pages::movie-index')->name('movies.index');
Route::livewire('/movies/{tmdbId}', 'pages::movie-detail')->name('movies.detail');
Route::livewire('/tv', 'pages::tv-index')->name('tv.index');
Route::livewire('/tv/{tmdbId}', 'pages::tv-detail')->name('tv.detail');
Route::livewire('/search', 'pages::search-page')->name('search');
Route::livewire('/watch/{type}/{tmdbId}/{season?}/{episode?}', 'pages::watch-page')->name('watch');

Route::livewire('/genres', 'pages::genre-index')->name('genres.index');
Route::livewire('/genres/{type}/{genreId}/{genreName}', 'pages::genre-browse')->name('genres.browse');
Route::livewire('/upcoming', 'pages::upcoming-index')->name('upcoming.index');
Route::livewire('/new-releases', 'pages::new-releases')->name('new-releases');

Route::livewire('/collections', 'pages::collections-index')->name('collections.index');
Route::livewire('/collections/{slug}', 'pages::collection-show')->name('collections.show');

Route::livewire('/people', 'pages::people-index')->name('people.index');
Route::livewire('/people/{personId}', 'pages::person-detail')->name('people.detail');

Route::livewire('/u/{userId}', 'pages::user-profile')->name('user.profile');

Route::livewire('/mood', 'pages::mood-picker')->name('mood.index');
Route::livewire('/discover', 'pages::discover')->name('discover');
Route::livewire('/trailers', 'pages::trailers-hub')->name('trailers');
Route::livewire('/leaderboard', 'pages::leaderboard')->name('leaderboard');
Route::livewire('/feed', 'pages::activity-feed')->name('activity.feed');
Route::livewire('/watch-parties', 'pages::watch-parties')->name('watch-parties');

Route::post('/api/affiliate-click', function (Request $request) {
    AffiliateClick::create([
        'user_id' => auth()->id(),
        'service_name' => $request->string('service_name')->limit(100),
        'service_id' => $request->string('service_id')->limit(50),
        'tmdb_id' => (int) $request->input('tmdb_id'),
        'media_type' => $request->string('media_type')->limit(10),
        'link' => $request->string('link')->limit(500),
        'ip_address' => $request->ip(),
    ]);

    return response()->json(['ok' => true]);
})->name('affiliate.click');

Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/architecture', 'pages.architecture')->name('architecture');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('/notifications', 'pages::notifications')->name('notifications');
});

Route::middleware(['auth', 'verified', 'adult.verified'])->group(function () {
    Route::livewire('/adult', 'pages::adult-browse')->name('adult.browse');
});

require __DIR__.'/settings.php';
