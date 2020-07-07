<?php

declare(strict_types=1);

namespace App\UseCase\Registration;

use Swagger\Annotations as SWG;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

/**
 * @SWG\Definition(
 *     type="object",
 *     description="Credentials for get jwt token and registration in cabinet",
 *     required={
 *         "botHash",
 *         "username",
 *         "password",
 *     }
 * )
 */
class RegistrationEntryDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     *
     * @SWG\Property(type="string", description="Hash from analytics bot", example="c5a4ab8c9e03887b81ddda6c514655ce")
     */
    private $botHash;

    /**
     * @var string
     *
     * @SWG\Property(type="string", description="Username for registration in cabinet", example="foo")
     */
    private $username;

    /**
     * @var string
     *
     * @SWG\Property(type="string", description="Password for registration in cabinet", example="bar")
     */
    private $password;

    /**
     * @return string
     */
    public function getBotHash(): string
    {
        return $this->botHash;
    }

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
}
