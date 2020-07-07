<?php

declare(strict_types=1);

namespace App\UseCase\AuthForm;

use App\Component\Csrf\CsrfSetterAwareInterface;
use App\Component\Csrf\CsrfSetterTrait;

class AuthFormHandler implements CsrfSetterAwareInterface
{
    use CsrfSetterTrait;

    public function handle(): void
    {
    }
}
