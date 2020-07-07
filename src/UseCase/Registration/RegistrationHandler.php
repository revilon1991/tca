<?php

declare(strict_types=1);

namespace App\UseCase\Registration;

use App\Component\Csrf\CsrfValidatorAwareInterface;
use App\Component\Csrf\CsrfValidatorTrait;
use App\Entity\User;
use App\Exception\ApiException;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationHandler implements CsrfValidatorAwareInterface
{
    use CsrfValidatorTrait;

    /**
     * @var RegistrationManager
     */
    private $manager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoderFactory;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param RegistrationManager $manager
     * @param EncoderFactoryInterface $encoderFactory
     * @param RequestStack $requestStack
     */
    public function __construct(
        RegistrationManager $manager,
        EncoderFactoryInterface $encoderFactory,
        RequestStack $requestStack
    ) {
        $this->manager = $manager;
        $this->encoderFactory = $encoderFactory;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param RegistrationEntryDto $entryDto
     *
     * @throws DBALException
     */
    public function handle(RegistrationEntryDto $entryDto): void
    {
        $userAgent = $this->request->headers->get('user-agent');
        $referer = $this->request->headers->get('referer');
        $clientIp = $this->request->getClientIp();
        $username = $entryDto->getUsername();
        $password = $entryDto->getPassword();
        $botHash = $entryDto->getBotHash();

        $user = $this->manager->getUserIdByUsername($username);

        if ($user) {
            throw new ApiException(ApiException::REGISTRATION_LOGIN_EXIST);
        }

        $user = $this->manager->getUserByBotHash($botHash);

        if (!$user) {
            throw new ApiException(ApiException::REGISTRATION_BOT_HASH_NOT_EXIST);
        }

        $encoder = $this->encoderFactory->getEncoder(User::class);
        $encodedPassword = $encoder->encodePassword($password, null);

        $subscriberId = $this->manager->getSubscriberId($user['subscriber_external_id']);

        $params = [
            'id' => $user['id'],
            'username' => $username,
            'password' => $encodedPassword,
            'roles' => json_encode(['ROLE_USER']),
            'subscriber_id' => $subscriberId,
            'actual_login_time' => date('Y-m-d H:i:s'),
            'actual_ip' => $clientIp,
            'actual_user_agent' => $userAgent,
            'referer' => $referer,
        ];

        $this->manager->updateUser($params);
    }
}
