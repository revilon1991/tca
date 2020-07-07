<?php

declare(strict_types=1);

namespace App\UseCase\ChangePasswordRestore;

use Swagger\Annotations as SWG;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

/**
 * @SWG\Definition(
 *     type="object",
 *     required={
 *         "successText",
 *         "redirectUrl",
 *     }
 * )
 */
class ChangePasswordRestoreResultDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     *
     * @SWG\Property(type="string")
     */
    private $successText;

    /**
     * @var string
     *
     * @SWG\Property(type="string")
     */
    private $redirectUrl;
}
