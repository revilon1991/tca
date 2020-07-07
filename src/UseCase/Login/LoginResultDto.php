<?php

declare(strict_types=1);

namespace App\UseCase\Login;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     type="object",
 *     required={
 *         "redirectUrl",
 *     }
 * )
 */
class LoginResultDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     *
     * @SWG\Property(type="string", example="/dashboard")
     */
    private $redirectUrl;
}
