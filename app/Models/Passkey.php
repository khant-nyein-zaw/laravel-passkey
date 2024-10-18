<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\PublicKeyCredentialSource;

class Passkey extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'credential_id', 'data'];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function data() : Attribute
    {
        return Attribute::make(
            get: fn ($value) => (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
                ->create()
                ->deserialize(
                    $value, PublicKeyCredentialSource::class, 'json'
                ),
            set: fn ($value) => [
                'credential_id' => $value->publicKeyCredentialId,
                'data' => json_encode($value),
            ]
        );
    }
}
