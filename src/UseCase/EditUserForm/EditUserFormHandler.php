<?php

declare(strict_types=1);

namespace App\UseCase\EditUserForm;

use App\Component\Csrf\CsrfSetterAwareInterface;
use App\Component\Csrf\CsrfSetterTrait;

class EditUserFormHandler implements CsrfSetterAwareInterface
{
    use CsrfSetterTrait;

    public function handle(): void
    {
    }
}
