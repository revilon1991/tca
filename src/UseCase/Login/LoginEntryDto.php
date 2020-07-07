<?php

declare(strict_types=1);

namespace App\UseCase\Login;

use Swagger\Annotations as SWG;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

/**
 * @SWG\Definition(
 *     type="object",
 *     description="Credentials for get jwt token and registration in cabinet",
 *     required={
 *         "username",
 *         "password",
 *         "rememberMe",
 *     }
 * )
 */
class LoginEntryDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     *
     * @SWG\Property(type="string", example="foo")
     */
    private $username;

    /**
     * @var string
     *
     * @SWG\Property(type="string", example="bar")
     */
    private $password;

    /**
     * @var boolean
     *
     * @SWG\Property(type="boolean", example=true)
     */
    private $rememberMe;

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
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }
}
