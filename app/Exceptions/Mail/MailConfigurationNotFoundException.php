<?php

declare(strict_types=1);

namespace App\Exceptions\Mail;

use RuntimeException;

final class MailConfigurationNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Configuração de e-mail ativa não encontrada para o tenant.');
    }
}