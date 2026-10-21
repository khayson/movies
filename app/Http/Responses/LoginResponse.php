<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        session()->forget('active_profile_id');

        if (! $request->wantsJson() && $request->user()->profiles()->count() > 1) {
            return redirect()->route('profiles.index');
        }

        $redirectUrl = redirect()->intended(Fortify::redirects('login'))->getTargetUrl();

        session()->flash('toast', [
            'slots' => [
                'heading' => __('Welcome back!'),
                'text' => __('You have been successfully logged in.'),
            ],
            'dataset' => ['variant' => 'success'],
            'duration' => 5000,
        ]);

        return $request->wantsJson()
            ? response()->json([
                'two_factor' => false,
                'redirect' => $redirectUrl,
            ])
            : redirect()->intended(Fortify::redirects('login'));
    }
}
