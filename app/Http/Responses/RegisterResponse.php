<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $redirectUrl = redirect()->intended(Fortify::redirects('register'))->getTargetUrl();

        session()->flash('toast', [
            'slots' => [
                'heading' => __('Welcome to StreamVault!'),
                'text' => __('Your account has been created successfully.'),
            ],
            'dataset' => ['variant' => 'success'],
            'duration' => 5000,
        ]);

        return $request->wantsJson()
            ? new JsonResponse([
                'redirect' => $redirectUrl,
            ], 201)
            : redirect()->intended(Fortify::redirects('register'));
    }
}
