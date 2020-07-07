<?php

declare(strict_types=1);

namespace App\UseCase\EditUser;

use App\Component\Csrf\CsrfValidatorAwareInterface;
use App\Component\Csrf\CsrfValidatorTrait;
use App\Component\JwtToken\Exception\JwtTokenException;
use App\Entity\User;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditUserHandler implements CsrfValidatorAwareInterface
{
    use CsrfValidatorTrait;

    /**
     * @var EditUserManager
     */
    private $manager;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TokenStorageInterface
     */
    private $authTokenStorage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param EditUserManager $manager
     * @param EncoderFactoryInterface $encoderFactory
     * @param TokenStorageInterface $authTokenStorage
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EditUserManager $manager,
        EncoderFactoryInterface $encoderFactory,
        TokenStorageInterface $authTokenStorage,
        TranslatorInterface $translator
    ) {
        $this->manager = $manager;
        $this->encoderFactory = $encoderFactory;
        $this->authTokenStorage = $authTokenStorage;
        $this->translator = $translator;
    }

    /**
     * @param EditUserEntryDto $entryDto
     *
     * @return array
     *
     * @throws JwtTokenException
     * @throws DBALException
     */
    public function handle(EditUserEntryDto $entryDto): array
    {
        $params['id'] = $this->getUser()->getId();

        $password = $entryDto->getPassword();

        if ($password) {
            $encoder = $this->encoderFactory->getEncoder(User::class);
            $encodedPassword = $encoder->encodePassword($password, null);

            $params['password'] = $encodedPassword;
        }

        $this->manager->saveUser($params);

        $result['success_text'] = $this->translator->trans('edit_user_form_success', [], 'cabinet');

        return $result;
    }

    /**
     * @return User
     *
     * @throws JwtTokenException
     */
    private function getUser(): User
    {
        $authToken = $this->authTokenStorage->getToken();

        if (!$authToken) {
            throw JwtTokenException::tokenNotReady();
        }

        /** @var User $user */
        $user = $authToken->getUser();

        return $user;
    }
}
