<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Component\JwtToken\Exception\JwtTokenException;
use App\Component\JwtToken\Handler\JwtTokenHandler;
use App\Entity\User;
use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class JwtUserProvider implements UserProviderInterface
{
    public const COOKIE_AUTHENTICATION_NAME = 'authentication';

    /**
     * @var JwtTokenHandler
     */
    private $jwtTokenHandler;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param JwtTokenHandler $jwtTokenHandler
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        JwtTokenHandler $jwtTokenHandler,
        EntityManagerInterface $entityManager
    ) {
        $this->jwtTokenHandler = $jwtTokenHandler;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     *
     * @throws JwtTokenException
     */
    public function loadUserByUsername($token): UserInterface
    {
        $tokenData = $this->jwtTokenHandler->decode($token);

        if (empty($tokenData['jti']) || empty($tokenData['username']) || empty($tokenData['roles'])) {
            throw new ApiException(ApiException::TOKEN_INVALID, 'Required information does not exist');
        }

        $userId = $tokenData['jti'];

        $user = new User();
        $user->setId($userId);
        $user->setUsername($tokenData['username']);
        $user->setRoles($tokenData['roles']);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $reloadedUser = $this->entityManager->getRepository(User::class)->find($user->getId());

        if (null === $reloadedUser) {
            throw new UnsupportedUserException("User with ID {$user->getId()} could not be reloaded.");
        }

        return $reloadedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class): bool
    {
        return $class === User::class;
    }
}
