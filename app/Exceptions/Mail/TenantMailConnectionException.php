<?php

declare(strict_types=1);

namespace App\Exceptions\Mail;

use RuntimeException;

final class TenantMailConnectionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Não foi possível conectar ao servidor de e-mail com as configurações informadas.');
    }
}
