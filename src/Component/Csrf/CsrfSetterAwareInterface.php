<?php

namespace App\Component\Csrf;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;

interface CsrfSetterAwareInterface
{
    public const CSRF_HEADER_NAME = 'X-CSRF-Token';

    /**
     * @param Request $request
     *
     * @return CsrfToken
     */
    public function makeCsrfToken(Request $request): CsrfToken;

    /**
     * @param CsrfToken $csrfToken
     * @param Response $response
     */
    public function setCsrfToken(CsrfToken $csrfToken, Response $response): void;
}
