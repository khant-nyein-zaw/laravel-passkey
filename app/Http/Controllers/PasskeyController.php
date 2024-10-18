<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Passkey;
use Illuminate\Http\Request;
use Webauthn\PublicKeyCredential;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Webauthn\AuthenticatorAssertionResponse;
use Illuminate\Validation\ValidationException;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

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

        $user = User::find($request->user()->id);

        $user->passkeys()->create([
            'name' => $data['name'],
            'credential_id' => $publicKeyCredentialSource->publicKeyCredentialId,
            'data' => $publicKeyCredentialSource
        ]);

        return to_route('profile.edit')->withFragment('managePasskeys');
    }

    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'answer' => ['required', 'json']
        ]);

        try {
            /** @var PublicKeyCredential $publicKeyCredential */
            $publicKeyCredential = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
                ->create()
                ->deserialize(
                    $data['answer'], PublicKeyCredential::class, 'json'
                );

            if (! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
                return to_route('profile.edit')->withFragment('managePasskeys');
            }

            $passkey = Passkey::where('credential_id', $publicKeyCredential->rawId)->first();

            if (! $passkey) {
                throw ValidationException::withMessages([
                    'answer' => 'The given passkey is invalid.',
                ]);
            }

            $publicKeyCredentialSource = AuthenticatorAssertionResponseValidator::create()->check(
                credentialId: $passkey->data,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: Session::get('passkey-authentication-options'),
                request: $request->getHost(),
                userHandle: null,
            );

        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw ValidationException::withMessages([
                'answer' => 'The given passkey is invalid.',
            ]);
        }

        $passkey->update([
            'data' => $publicKeyCredentialSource
        ]);

        Auth::loginUsingId($passkey->user_id);
        $request->session()->regenerate();

        return to_route('dashboard');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Passkey $passkey)
    {
        $passkey->delete();
        return Redirect::back()->withFragment('managePasskeys');
    }
}
