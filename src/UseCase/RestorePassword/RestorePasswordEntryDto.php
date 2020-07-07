<?php

declare(strict_types=1);

namespace App\UseCase\RestorePassword;

use Swagger\Annotations as SWG;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;
use App\Enum\RestoreMethodEnum;

/**
 * @SWG\Definition(
 *     type="object",
 *     required={
 *         "email",
 *         "method",
 *     }
 * )
 */
class RestorePasswordEntryDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     *
     * @SWG\Property(type="string", example="foo@bar.com", format="email")
     */
    private $username;

    /**
     * @var string
     *
     * @SWG\Property(type="string", enum={RestoreMethodEnum::EMAIL, RestoreMethodEnum::TELEGRAM})
     */
    private $method;

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
