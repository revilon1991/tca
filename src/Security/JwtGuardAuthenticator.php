<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Translation\Translator;
use function strlen;
use function strpos;
use function substr;

class JwtGuardAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request): string
    {
        $authString = $request->headers->get('Authorization');

        if (empty($authString)) {
            throw new ApiException(ApiException::TOKEN_NOT_FOUND);
        }

        $prefix = 'Bearer ';

        if (strpos($authString, $prefix) !== 0) {
            throw new ApiException(ApiException::TOKEN_INVALID);
        }

        return substr($authString, strlen($prefix));
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($token, UserProviderInterface $userProvider): UserInterface
    {
        return $userProvider->loadUserByUsername($token);
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            throw new ApiException(ApiException::TOKEN_INVALID);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        throw new ApiException(ApiException::HTTP_UNAUTHORIZED);
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        throw new ApiException(ApiException::HTTP_UNAUTHORIZED);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe(): bool
    {
        return true;
    }
}
