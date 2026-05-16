<?php

declare(strict_types=1);

namespace App\Enums;

enum AdminMenuPermissionStrategy: string
{
    case ANY = 'any';
    case ALL = 'all';
}
