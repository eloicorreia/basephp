<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleCode: string
{
    case ADMIN = 'admin';
    case EMPRESA = 'empresa';
    case USUARIO = 'usuario';
}