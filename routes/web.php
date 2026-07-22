<?php

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

Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/architecture', 'pages.architecture')->name('architecture');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
