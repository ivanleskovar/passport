<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Laravel\Passport\DeviceCode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;

class DeviceAccessTokenController
{
    /**
     * The token repository implementation.
     *
     * @var \Laravel\Passport\TokenRepository
     */
    protected $tokenRepository;

    /**
     * The validation factory implementation.
     *
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected $validation;

    /**
     * Create a controller instance.
     *
     * @param  \Laravel\Passport\TokenRepository  $tokenRepository
     * @param  \Illuminate\Contracts\Validation\Factory  $validation
     * @return void
     */
    public function __construct(TokenRepository $tokenRepository, ValidationFactory $validation)
    {
        $this->validation = $validation;
        $this->tokenRepository = $tokenRepository;
    }

    public function check(Request $request, $userCode)
    {
        return DeviceCode::where('user_code', $userCode)->first();
    }

    /**
     * Get all of the personal access tokens for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser(Request $request)
    {
        $tokens = $this->tokenRepository->forUser($request->user()->getKey());

        return $tokens->load('client')->filter(function ($token) {
            return $token->client->device_client && ! $token->revoked;
        })->values();
    }

    /**
     * Activate new device for the user.
     * @fix this response myst be created..
     * @param  \Illuminate\Http\Request  $request
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function store(Request $request)
    {
        $validate = [
            'user_code' => [
                'required',
                'exists:' . with(new DeviceCode)->getTable() . ',user_code'
            ],
        ];

        $errors = [
            'user_code.exists' => 'This code do not exist please try again.',
        ];

        $this->validation->make($request->all(), $validate, $errors)->validate();

        return $request->user()->activateDevice($request->user_code);
    }

    /**
     * Delete the given token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $tokenId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $tokenId)
    {
        $token = $this->tokenRepository->findForUser(
            $tokenId, $request->user()->getKey()
        );

        if (is_null($token)) {
            return new Response('', 404);
        }

        $token->revoke();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
