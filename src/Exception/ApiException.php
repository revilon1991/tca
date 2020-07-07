<?php

declare(strict_types=1);

namespace App\Exception;

use Wakeapp\Bundle\ApiPlatformBundle\Exception\ApiException as ApiPlatformException;

class ApiException extends ApiPlatformException
{
    // Database Errors 1 - 9
    public const DATABASE_UNEXPECTED_ERROR = 1;

    // Auth errors 6xx
    public const REGISTRATION_LOGIN_EXIST = 600;
    public const AUTH_CLIENT_IP_ERROR = 601;
    public const REGISTRATION_BOT_HASH_NOT_EXIST = 602;
    public const LOGIN_USERNAME_NOT_EXIST = 603;
    public const RESTORE_PASSWORD_EMAIL_NOT_EXIST = 604;
    public const RESTORE_PASSWORD_METHOD_UNDEFINED = 605;
    public const RESTORE_PASSWORD_USER_NOT_FOUND = 606;
    public const RESTORE_PASSWORD_LINK_OLDER = 607;

    // Token Errors 8xx
    public const TOKEN_ENCODE_FAIL = 800;
    public const TOKEN_NOT_FOUND = 801;
    public const TOKEN_INVALID = 802;
    public const TOKEN_CSRF_INVALID = 803;
}
