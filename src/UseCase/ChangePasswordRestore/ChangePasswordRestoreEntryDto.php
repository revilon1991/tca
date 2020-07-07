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
 *         "context",
 *         "password",
 *     }
 * )
 */
class ChangePasswordRestoreEntryDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     *
     * @SWG\Property(type="string")
     */
    private $context;

    /**
     * @var string
     *
     * @SWG\Property(type="string", minimum=6, maximum=24)
     */
    private $password;

    /**
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}
