<?php

declare(strict_types=1);

namespace App\UseCase\Login;

use App\Component\Csrf\CsrfValidatorAwareInterface;
use App\Component\Csrf\CsrfValidatorTrait;
use App\Component\JwtToken\Handler\JwtTokenHandler;
use App\Entity\User;
use App\Exception\ApiException;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class LoginHandler implements CsrfValidatorAwareInterface
{
    use CsrfValidatorTrait;

    /**
     * @var LoginManager
     */
    private $manager;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var JwtTokenHandler
     */
    private $jwtTokenHandler;

    /**
     * @param LoginManager $manager
     * @param RequestStack $requestStack
     * @param EncoderFactoryInterface $encoderFactory
     * @param JwtTokenHandler $jwtTokenHandler
     */
    public function __construct(
        LoginManager $manager,
        RequestStack $requestStack,
        EncoderFactoryInterface $encoderFactory,
        JwtTokenHandler $jwtTokenHandler
    ) {
        $this->manager = $manager;
        $this->request = $requestStack->getCurrentRequest();
        $this->encoderFactory = $encoderFactory;
        $this->jwtTokenHandler = $jwtTokenHandler;
    }

    /**
     * @param LoginEntryDto $entryDto
     *
     * @return string
     *
     * @throws DBALException
     */
    public function handle(LoginEntryDto $entryDto): string
    {
        $userAgent = $this->request->headers->get('user-agent');
        $clientIp = $this->request->getClientIp();
        $username = $entryDto->getUsername();
        $password = $entryDto->getPassword();

        $encoder = $this->encoderFactory->getEncoder(User::class);
        $encodedPassword = $encoder->encodePassword($password, null);

        if (!$encoder->isPasswordValid($encodedPassword, $password, null)) {
            throw new ApiException(ApiException::LOGIN_USERNAME_NOT_EXIST);
        }

        $user = $this->manager->getUser($username);

        $this->manager->updateUser($user['id'], $userAgent, $clientIp);

        $payload = [
            'username' => $user['username'],
            'roles' => json_decode($user['roles'], true),
        ];

        return $this->jwtTokenHandler->encode($payload, $user['id']);
    }
}
