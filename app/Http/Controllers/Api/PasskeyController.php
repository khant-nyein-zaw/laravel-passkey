<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;

class PasskeyController extends Controller
{
    public function registerOptions(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $options = new PublicKeyCredentialCreationOptions(
            rp: new PublicKeyCredentialRpEntity(
                name: config('app.name'),
                id: parse_url(config('app.url'), PHP_URL_HOST)
            ),
            user: new PublicKeyCredentialUserEntity(
                name: $request->user()->email,
                id: $request->user()->id,
                displayName: $request->user()->name
            ),
            challenge: Str::random(),
            authenticatorSelection: new AuthenticatorSelectionCriteria(
                authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED
            )
        );

        Session::flash('passkey-registration-options', $options);

        return $options;
    }
}
