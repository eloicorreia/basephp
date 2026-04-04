<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\DTOs\Mail\TenantMailConfigData;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

final class RuntimeMailTransportFactory
{
    public function make(TenantMailConfigData $config): EsmtpTransport
    {
        $transport = new EsmtpTransport(
            host: $config->host,
            port: $config->port,
            tls: $config->encryption === 'tls'
        );

        if ($config->username !== null) {
            $transport->setUsername($config->username);
        }

        if ($config->password !== null) {
            $transport->setPassword($config->password);
        }

        $stream = $transport->getStream();
        $stream->setTimeout($config->timeoutSeconds);
        $streamOptions = $stream->getStreamOptions();

        $streamOptions['ssl'] = [
            'verify_peer' => $config->verifyPeer,
            'verify_peer_name' => $config->verifyPeerName,
            'allow_self_signed' => $config->allowSelfSigned,
        ];

        $stream->setStreamOptions($streamOptions);

        return $transport;
    }
}