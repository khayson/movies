<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchProfileController extends Controller
{
    /**
     * Make one of the signed-in account's profiles the active viewer.
     */
    public function __invoke(Request $request, Profile $profile): RedirectResponse
    {
        $request->user()->switchProfile($profile);

        return redirect()->intended(route('home'));
    }
}
