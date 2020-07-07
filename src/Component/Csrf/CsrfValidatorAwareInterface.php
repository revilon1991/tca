<?php

namespace App\Component\Csrf;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;

interface CsrfValidatorAwareInterface
{
    /**
     * @param CsrfToken $csrfToken
     */
    public function validateCsrf(CsrfToken $csrfToken): void;

    /**
     * @param Request $request
     *
     * @return CsrfToken
     */
    public function getCsrfToken(Request $request): CsrfToken;
}
