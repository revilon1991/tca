<?php

declare(strict_types=1);

namespace App\UseCase\RestorePassword;

use Swagger\Annotations as SWG;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

/**
 * @SWG\Definition(
 *     type="object",
 *     required={
 *         "completeText",
 *     }
 * )
 */
class RestorePasswordResultDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     *
     * @SWG\Property(type="string")
     */
    private $completeText;
}
