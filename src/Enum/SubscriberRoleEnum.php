<?php

declare(strict_types=1);

namespace App\Enum;

class SubscriberRoleEnum extends AbstractEnum
{
    public const USER = 'user';
    public const CREATOR = 'creator';
    public const ADMIN = 'admin';
}
