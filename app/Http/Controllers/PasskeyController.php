<?php

namespace App\Http\Controllers;

use App\Models\Passkey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webauthn\PublicKeyCredential;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorAttestationResponse;

class PasskeyController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validateWithBag('createPasskey', [
            'name' => ['required', 'string', 'max:255'],
            'passkey' => ['required', 'json']
        ]);

        try {
            /** @var PublicKeyCredential $publicKeyCredential */
            $publicKeyCredential = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
                ->create()
                ->deserialize(
                    $data['passkey'], PublicKeyCredential::class, 'json'
                );

            if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
                return to_route('login');
            }

            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create()->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: Session::get('passkey-registration-options'),
                request: $request->getHost()
            );

        } catch (\Throwable $th) {
            Log::error($th->getMessage(), $th->getTrace());
            throw ValidationException::withMessages([
                'name' => 'The given passkey is invalid.',
            ])->errorBag('createPasskey');
        }

        $request->user()->passkeys()->create([
            'name' => $data['name'],
            'credential_id' => $publicKeyCredentialSource->publicKeyCredentialId,
            'data' => $publicKeyCredentialSource
        ]);

        return to_route('profile.edit');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Passkey $passkey)
    {
        $passkey->delete();
        return Redirect::back();
    }
}
