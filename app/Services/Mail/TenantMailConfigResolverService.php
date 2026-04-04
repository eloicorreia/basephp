<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\DTOs\Mail\TenantMailConfigData;
use App\Exceptions\Mail\MailConfigurationInvalidException;
use App\Exceptions\Mail\MailConfigurationNotFoundException;
use App\Models\MailConfig;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;

final readonly class TenantMailConfigResolverService
{
    public function __construct(
        private Encrypter $encrypter,
    ) {
    }

    public function resolveDefault(): TenantMailConfigData
    {
        $config = MailConfig::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if ($config === null) {
            throw new MailConfigurationNotFoundException();
        }

        if ($config->driver !== 'smtp') {
            throw new MailConfigurationInvalidException('Apenas o driver smtp é suportado nesta fase.');
        }

        $password = null;

        if ($config->password_encrypted !== null) {
            try {
                $password = $this->encrypter->decryptString($config->password_encrypted);
            } catch (DecryptException $exception) {
                throw new MailConfigurationInvalidException(
                    'Não foi possível descriptografar a senha SMTP.'
                );
            }
        }

        return new TenantMailConfigData(
            id: (int) $config->id,
            name: (string) $config->name,
            driver: (string) $config->driver,
            host: (string) $config->host,
            port: (int) $config->port,
            encryption: $config->encryption,
            username: $config->username,
            password: $password,
            fromAddress: (string) $config->from_address,
            fromName: (string) $config->from_name,
            replyToAddress: $config->reply_to_address,
            replyToName: $config->reply_to_name,
            timeoutSeconds: (int) $config->timeout_seconds,
            verifyPeer: (bool) $config->verify_peer,
            verifyPeerName: (bool) $config->verify_peer_name,
            allowSelfSigned: (bool) $config->allow_self_signed,
        );
    }
}