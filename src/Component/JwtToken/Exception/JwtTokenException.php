<?php

declare(strict_types=1);

namespace App\Component\JwtToken\Exception;

use Exception;

class JwtTokenException extends Exception
{
    /**
     * @param string $message
     *
     * @return self
     */
    public static function parseFailed(string $message): self
    {
        return new self(sprintf('Token parsing has been failed. Message: %s', $message));
    }

    /**
     * @return self
     */
    public static function tokenExpired(): self
    {
        return new self('Token has been expired');
    }

    /**
     * @return self
     */
    public static function tokenNotReady(): self
    {
        return new self('Token is yet not ready to work');
    }

    /**
     * @param string $failedOn
     *
     * @return self
     */
    public static function tokenInvalid(string $failedOn): self
    {
        return new self(sprintf('Token invalid because of "%s" is different', $failedOn));
    }

    /**
     * @param string $message
     *
     * @return self
     */
    public static function verifyFailed(string $message): self
    {
        return new self(sprintf('Token verification has been failed. Message: %s', $message));
    }
}
