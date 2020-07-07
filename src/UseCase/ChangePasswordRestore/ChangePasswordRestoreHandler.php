<?php

declare(strict_types=1);

namespace App\UseCase\ChangePasswordRestore;

use App\Component\Csrf\CsrfValidatorAwareInterface;
use App\Component\Csrf\CsrfValidatorTrait;
use App\Component\JwtToken\Exception\JwtTokenException;
use App\Component\JwtToken\Handler\JwtTokenHandler;
use App\Entity\User;
use App\Exception\ApiException;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordRestoreHandler implements CsrfValidatorAwareInterface
{
    use CsrfValidatorTrait;

    /**
     * @var ChangePasswordRestoreManager
     */
    private $manager;

    /**
     * @var JwtTokenHandler
     */
    private $jwtTokenHandler;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param ChangePasswordRestoreManager $manager
     * @param JwtTokenHandler $jwtTokenHandler
     * @param EncoderFactoryInterface $encoderFactory
     * @param TranslatorInterface $translator
     * @param RouterInterface $router
     */
    public function __construct(
        ChangePasswordRestoreManager $manager,
        JwtTokenHandler $jwtTokenHandler,
        EncoderFactoryInterface $encoderFactory,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->manager = $manager;
        $this->jwtTokenHandler = $jwtTokenHandler;
        $this->encoderFactory = $encoderFactory;
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * @param ChangePasswordRestoreEntryDto $entryDto
     *
     * @return array
     *
     * @throws JwtTokenException
     * @throws DBALException
     */
    public function handle(ChangePasswordRestoreEntryDto $entryDto): array
    {
        $contextEncoded = $entryDto->getContext();
        $password = $entryDto->getPassword();

        $context = $this->jwtTokenHandler->decode($contextEncoded);

        $userId = $context['user_id'];
        $userUpdatedAt = $context['user_updated_at'];

        $user = $this->manager->getUser($userId);

        if (!$userId) {
            throw new ApiException(ApiException::RESTORE_PASSWORD_USER_NOT_FOUND);
        }

        if ($user['updated_at'] !== $userUpdatedAt) {
            throw new ApiException(ApiException::RESTORE_PASSWORD_LINK_OLDER);
        }

        $encoder = $this->encoderFactory->getEncoder(User::class);
        $encodedPassword = $encoder->encodePassword($password, null);

        $this->manager->saveUser([
            'id' => $userId,
            'password' => $encodedPassword,
        ]);

        return [
            'redirectUrl' => $this->router->generate('app_auth_login'),
            'successText' => $this->translator->trans('change_password_restore_form_success', [], 'cabinet'),
        ];
    }
}
