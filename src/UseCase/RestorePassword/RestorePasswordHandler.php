<?php

declare(strict_types=1);

namespace App\UseCase\RestorePassword;

use App\Component\Csrf\CsrfValidatorAwareInterface;
use App\Component\Csrf\CsrfValidatorTrait;
use App\Component\JwtToken\Handler\JwtTokenHandler;
use App\Component\Tarantool\Adapter\TarantoolQueueAdapter;
use App\Consumer\SendMailConsumer;
use App\Consumer\SendTelegramMessageConsumer;
use App\Enum\RestoreMethodEnum;
use App\Exception\ApiException;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RestorePasswordHandler implements CsrfValidatorAwareInterface
{
    use CsrfValidatorTrait;

    /**
     * @var RestorePasswordManager
     */
    private $manager;

    /**
     * @var TarantoolQueueAdapter
     */
    private $tarantoolQueueAdapter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var JwtTokenHandler
     */
    private $jwtTokenHandler;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RestorePasswordManager $manager
     * @param TarantoolQueueAdapter $tarantoolQueueAdapter
     * @param TranslatorInterface $translator
     * @param JwtTokenHandler $jwtTokenHandler
     * @param RouterInterface $router
     */
    public function __construct(
        RestorePasswordManager $manager,
        TarantoolQueueAdapter $tarantoolQueueAdapter,
        TranslatorInterface $translator,
        JwtTokenHandler $jwtTokenHandler,
        RouterInterface $router
    ) {
        $this->manager = $manager;
        $this->tarantoolQueueAdapter = $tarantoolQueueAdapter;
        $this->translator = $translator;
        $this->jwtTokenHandler = $jwtTokenHandler;
        $this->router = $router;
    }

    /**
     * @param RestorePasswordEntryDto $entryDto
     *
     * @return array
     *
     * @throws Exception
     */
    public function handle(RestorePasswordEntryDto $entryDto): array
    {
        $username = $entryDto->getUsername();
        $restoreMethod = $entryDto->getMethod();

        $user = $this->manager->getUser($username);

        if (!$user) {
            throw new ApiException(ApiException::RESTORE_PASSWORD_EMAIL_NOT_EXIST);
        }

        switch ($restoreMethod) {
            case RestoreMethodEnum::EMAIL:
                $this->tarantoolQueueAdapter->put(SendMailConsumer::QUEUE_SEND_MAIL, [
                    'email' => $user['email'],
                    'subject' => 'Restore Password Telegram Channel Analytics',
                    'text' => $this->makeText($user, $restoreMethod),
                ]);

                $translationParams = [
                    '%email%' => $this->formatEmail($user['email']),
                ];

                $completeText = $this->translator->trans(
                    'restore_password_form_email_success',
                    $translationParams,
                    'cabinet'
                );

                break;

            case RestoreMethodEnum::TELEGRAM:
                $this->tarantoolQueueAdapter->put(SendTelegramMessageConsumer::QUEUE_SEND_TELEGRAM_MESSAGE, [
                    'subscriber_external_id' => $user['subscriber_external_id'],
                    'text' => $this->makeText($user, $restoreMethod),
                ]);

                $completeText = $this->translator->trans('restore_password_form_telegram_success', [], 'cabinet');

                break;

            default:
                throw new ApiException(ApiException::RESTORE_PASSWORD_METHOD_UNDEFINED);
        }



        return [
            'complete_text' => $completeText,
        ];
    }

    /**
     * @param string $email
     *
     * @return string
     */
    private function formatEmail(string $email): string
    {
        $emailPartList = explode('@', $email);

        $result = '';

        foreach (str_split($emailPartList[0]) as $key => $char) {
            if ($key === 0) {
                $result = $char;
            }

            $result .= '*';
        }

        $result .= '@' . $emailPartList[1];

        return $result;
    }

    /**
     * @param array $user
     *
     * @param string $restoreMethod
     *
     * @return string
     */
    private function makeText(array $user, string $restoreMethod): string
    {
        $text = 'Restore link ';

        $jwtToken = $this->jwtTokenHandler->encode([
            'user_id' => $user['id'],
            'restore_method' => $restoreMethod,
            'user_updated_at' => $user['updated_at'],
        ]);

        $text .= $this->router->generate(
            'app_auth_changepasswordrestoreform',
            [
                'context' => $jwtToken,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $text;
    }
}
