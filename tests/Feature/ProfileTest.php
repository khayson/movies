<?php

use App\Models\Profile;
use App\Models\User;
use App\Models\WatchHistory;
use App\Models\Watchlist;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Http::fake(['*' => Http::response(['results' => [], 'total_pages' => 1])]);
});

test('every account gets a default profile named after the user', function () {
    $user = User::factory()->create(['name' => 'Ama Mensah']);

    expect($user->activeProfile()->name)->toBe('Ama')
        ->and($user->profiles()->count())->toBe(1);
});

test('viewing data is kept separately for each profile', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $first = $user->activeProfile();
    $second = $user->profiles()->create(['name' => 'Kids']);

    Watchlist::factory()->for($user)->create(['tmdb_id' => 550, 'media_type' => 'movie']);

    expect($user->watchlist()->count())->toBe(1)
        ->and(Watchlist::first()->profile_id)->toBe($first->id);

    $user->switchProfile($second);

    expect($user->watchlist()->count())->toBe(0)
        ->and($user->hasOnWatchlist(550, 'movie'))->toBeFalse();

    $user->watchlist()->create(['tmdb_id' => 550, 'media_type' => 'movie', 'title' => 'Fight Club']);

    expect(Watchlist::where('profile_id', $second->id)->count())->toBe(1)
        ->and(Watchlist::count())->toBe(2);
});

test('account-wide counts still include every profile', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    WatchHistory::factory()->for($user)->create(['tmdb_id' => 1]);
    $user->switchProfile($user->profiles()->create(['name' => 'Second']));
    WatchHistory::factory()->for($user)->create(['tmdb_id' => 2]);

    expect(User::withCount('watchHistory')->find($user->id)->watch_history_count)->toBe(2)
        ->and($user->watchHistory()->count())->toBe(1);
});

test('who is watching page lets the user pick a profile', function () {
    $user = User::factory()->create();
    $kids = Profile::factory()->for($user)->create(['name' => 'Kids']);

    $this->actingAs($user)->get(route('profiles.index'))
        ->assertOk()
        ->assertSee("Who's watching?")
        ->assertSee('Kids');

    Livewire::actingAs($user)->test('pages::profiles')
        ->call('select', $kids->id)
        ->assertRedirect(route('home'));

    expect(session('active_profile_id'))->toBe($kids->id);
});

test('profiles can be added, renamed and deleted but never the last one', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $default = $user->activeProfile();

    $component = Livewire::test('pages::profiles')
        ->call('startCreating')
        ->set('name', 'Kofi')
        ->set('avatarColor', 'sky')
        ->call('save')
        ->assertHasNoErrors();

    $kofi = $user->profiles()->where('name', 'Kofi')->firstOrFail();
    expect($kofi->avatar_color)->toBe('sky');

    $component->call('startEditing', $kofi->id)->set('name', 'Kofi Jr')->call('save');
    expect($kofi->fresh()->name)->toBe('Kofi Jr');

    $component->call('delete', $kofi->id);
    expect($user->profiles()->count())->toBe(1);

    $component->call('delete', $default->id)->assertForbidden();
});

test('an account cannot have more than five profiles', function () {
    $user = User::factory()->create();
    Profile::factory()->count(Profile::MAX_PER_ACCOUNT)->for($user)->create();

    Livewire::actingAs($user)->test('pages::profiles')
        ->call('startCreating')
        ->assertForbidden();
});

test('users cannot switch to or edit another account profile', function () {
    $user = User::factory()->create();
    $otherProfile = Profile::factory()->create();

    $this->actingAs($user)
        ->post(route('profiles.switch', $otherProfile))
        ->assertNotFound();

    Livewire::actingAs($user)->test('pages::profiles')
        ->call('startEditing', $otherProfile->id)
        ->assertNotFound();
});

test('switching from the account menu changes the active profile', function () {
    $user = User::factory()->create();
    $kids = Profile::factory()->for($user)->create(['name' => 'Kids']);

    $this->actingAs($user)
        ->post(route('profiles.switch', $kids))
        ->assertRedirect(route('home'));

    expect(session('active_profile_id'))->toBe($kids->id);
});

test('signing in to an account with several profiles shows the profile picker', function () {
    $user = User::factory()->create();
    Profile::factory()->count(2)->for($user)->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('profiles.index'));
});
