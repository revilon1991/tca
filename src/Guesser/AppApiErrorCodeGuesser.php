<?php

declare(strict_types=1);

namespace App\Guesser;

use App\Component\Csrf\Exception\CsrfIdentifyUserException;
use App\Component\Csrf\Exception\CsrfValidationException;
use App\Component\JwtToken\Exception\JwtTokenException;
use App\Exception\ApiException;
use Doctrine\DBAL\DBALException;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Throwable;
use Wakeapp\Bundle\ApiPlatformBundle\Guesser\ApiErrorCodeGuesser;

class AppApiErrorCodeGuesser extends ApiErrorCodeGuesser
{
    /**
     * {@inheritdoc}
     */
    public function guessErrorCode(Throwable $exception): ?int
    {
        if ($exception instanceof DBALException) {
            return ApiException::DATABASE_UNEXPECTED_ERROR;
        }

        if ($exception instanceof JwtTokenException) {
            return ApiException::TOKEN_INVALID;
        }

        if ($exception instanceof InvalidArgumentException) {
            return ApiException::HTTP_BAD_REQUEST_DATA;
        }

        if ($exception instanceof CsrfIdentifyUserException) {
            return ApiException::AUTH_CLIENT_IP_ERROR;
        }

        if ($exception instanceof CsrfValidationException) {
            return ApiException::TOKEN_CSRF_INVALID;
        }

        return parent::guessErrorCode($exception);
    }
}
