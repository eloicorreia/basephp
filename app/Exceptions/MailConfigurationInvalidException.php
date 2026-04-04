<?php

declare(strict_types=1);

namespace App\Exceptions\Mail;

use RuntimeException;

final class MailConfigurationInvalidException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}