<?php

declare(strict_types=1);

namespace App\UseCase\EditUser;

use Swagger\Annotations as SWG;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

/**
 * @SWG\Definition(
 *     type="object",
 * )
 */
class EditUserEntryDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string|null
     *
     * @SWG\Property(type="string", example="tICNYGzNQQsD", minimum=6, maximum=24)
     */
    private $password;

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }
}
