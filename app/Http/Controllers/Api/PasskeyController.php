<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

class PasskeyController extends Controller
{
    public function registerOptions(Request $request)
    {
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
                authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM,
                userVerification: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
            )
        );

        return (new WebauthnSerializerFactory(
            AttestationStatementSupportManager::create()
        ))->create()->serialize(data: $options, format: 'json');
    }
}
