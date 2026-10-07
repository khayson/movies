<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        // Email verification is temporarily disabled — mark new accounts as verified.
        $user->forceFill(['email_verified_at' => now()])->save();

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'account',
            'title' => "Welcome to StreamVault, {$user->name}!",
            'message' => 'Your account is all set. Start exploring movies, build your watchlist, and get personalized recommendations based on what you watch.',
            'link' => '/dashboard',
        ]);

        return $user;
    }
}
