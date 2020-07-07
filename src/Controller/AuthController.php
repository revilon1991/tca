<?php

declare(strict_types=1);

namespace App\Controller;

use App\Component\JwtToken\Exception\JwtTokenException;
use App\Dto\ApiDocResultDto;
use App\Enum\TtlEnum;
use App\Security\User\JwtUserProvider;
use App\UseCase\AuthForm\AuthFormHandler;
use App\UseCase\ChangePasswordRestore\ChangePasswordRestoreEntryDto;
use App\UseCase\ChangePasswordRestore\ChangePasswordRestoreHandler;
use App\UseCase\ChangePasswordRestore\ChangePasswordRestoreResultDto;
use App\UseCase\ChangePasswordRestoreForm\ChangePasswordRestoreFormException;
use App\UseCase\ChangePasswordRestoreForm\ChangePasswordRestoreFormHandler;
use App\UseCase\Login\LoginEntryDto;
use App\UseCase\Login\LoginHandler;
use App\UseCase\Login\LoginResultDto;
use App\UseCase\Registration\RegistrationEntryDto;
use App\UseCase\Registration\RegistrationHandler;
use App\UseCase\RestorePassword\RestorePasswordEntryDto;
use App\UseCase\RestorePassword\RestorePasswordHandler;
use App\UseCase\RestorePassword\RestorePasswordResultDto;
use Doctrine\DBAL\DBALException;
use Exception;
use Nelmio\ApiDocBundle\Annotation\Model;
use Psr\Log\LoggerInterface;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Wakeapp\Bundle\ApiPlatformBundle\Factory\ApiDtoFactory;
use Wakeapp\Bundle\ApiPlatformBundle\HttpFoundation\ApiResponse;

/**
 * @SWG\Tag(name="Auth")
 * @SWG\Response(response="default", @Model(type=ApiDocResultDto::class), description="Response wrapper")
 */
class AuthController extends AbstractController
{
    /**
     * @var ApiDtoFactory
     */
    private $apiDtoFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ApiDtoFactory $apiDtoFactory
     * @param LoggerInterface $logger
     */
    public function __construct(ApiDtoFactory $apiDtoFactory, LoggerInterface $logger)
    {
        $this->apiDtoFactory = $apiDtoFactory;
        $this->logger = $logger;
    }

    /**
     * @Route(path="/", methods={"GET"})
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->redirectToRoute('app_auth_authform');
    }

    /**
     * @Route(path="/cabinet/auth", methods={"GET"})
     *
     * @param AuthFormHandler $handler
     *
     * @return Response
     */
    public function authForm(AuthFormHandler $handler): Response
    {
        $handler->handle();

        return $this->render('Auth/auth.html.twig');
    }

    /**
     * @Route(path="/cabinet/api/registration", methods={"POST"})
     *
     * @SWG\Parameter(name="body", in="body", @Model(type=RegistrationEntryDto::class), required=true)
     * @SWG\Response(
     *      response=ApiResponse::HTTP_OK,
     *      description="Successful result in 'data' offset",
     * )
     *
     * @param RegistrationEntryDto $entryDto
     * @param RegistrationHandler $handler
     *
     * @return Response
     *
     * @throws DBALException
     */
    public function registration(RegistrationEntryDto $entryDto, RegistrationHandler $handler): Response
    {
        $handler->handle($entryDto);

        return new ApiResponse();
    }

    /**
     * @Route(path="/cabinet/api/login", methods={"POST"})
     *
     * @SWG\Parameter(name="body", in="body", @Model(type=LoginEntryDto::class), required=true)
     * @SWG\Response(
     *      response=ApiResponse::HTTP_OK,
     *      description="Successful result in 'data' offset",
     *      @Model(type=LoginResultDto::class)
     * )
     *
     * @param LoginEntryDto $entryDto
     * @param LoginHandler $handler
     * @param string $domain
     *
     * @return Response
     *
     * @throws DBALException
     */
    public function login(LoginEntryDto $entryDto, LoginHandler $handler, string $domain): Response
    {
        $jwtToken = $handler->handle($entryDto);

        $redirectUrl = $this->generateUrl('app_report_dashboard');

        $loginResultDto = $this->apiDtoFactory->createApiDto(LoginResultDto::class, [
            'redirectUrl' => $redirectUrl,
        ]);

        $apiResponse = new ApiResponse($loginResultDto);

        $apiResponse->headers->setCookie(Cookie::create(
            JwtUserProvider::COOKIE_AUTHENTICATION_NAME,
            $jwtToken,
            TtlEnum::TTL_1_DAY,
            '/',
            $domain
        ));

        return $apiResponse;
    }

    /**
     * @Route(path="/cabinet/api/restore_password", methods={"POST"})
     *
     * @SWG\Parameter(name="body", in="body", @Model(type=RestorePasswordEntryDto::class), required=true)
     * @SWG\Response(
     *      response=ApiResponse::HTTP_OK,
     *      description="Successful result in 'data' offset",
     *      @Model(type=RestorePasswordResultDto::class)
     * )
     *
     * @param RestorePasswordHandler $handler
     * @param RestorePasswordEntryDto $entryDto
     *
     * @return Response
     *
     * @throws Exception
     */
    public function restorePassword(RestorePasswordHandler $handler, RestorePasswordEntryDto $entryDto): Response
    {
        $result = $handler->handle($entryDto);

        $resultDto = $this->apiDtoFactory->createApiDto(RestorePasswordResultDto::class, $result);

        return new ApiResponse($resultDto);
    }

    /**
     * @Route(path="/cabinet/change_password_restore", methods={"GET"})
     *
     * @param ChangePasswordRestoreFormHandler $handler
     * @param Request $request
     *
     * @return Response
     */
    public function changePasswordRestoreForm(ChangePasswordRestoreFormHandler $handler, Request $request): Response
    {
        $contextEncoded = $request->query->get('context');

        try {
            $handler->handle($contextEncoded);
        } catch (ChangePasswordRestoreFormException|DBALException|JwtTokenException $exception) {
            $this->logger->info("Change password after restore fail: {$exception->getMessage()}");

            $urlRegistration = $this->generateUrl('app_auth_registration');

            return new RedirectResponse($urlRegistration);
        }

        return $this->render('Auth/changePasswordRestore.html.twig');
    }

    /**
     * @Route(path="/cabinet/api/change_password_restore", methods={"POST"})
     *
     * @SWG\Parameter(name="body", in="body", @Model(type=ChangePasswordRestoreEntryDto::class), required=true)
     * @SWG\Response(
     *      response=ApiResponse::HTTP_OK,
     *      description="Successful result in 'data' offset",
     *      @Model(type=ChangePasswordRestoreResultDto::class)
     * )
     *
     * @param ChangePasswordRestoreHandler $handler
     * @param ChangePasswordRestoreEntryDto $entryDto
     *
     * @return Response
     *
     * @throws Exception
     */
    public function changePasswordRestore(
        ChangePasswordRestoreHandler $handler,
        ChangePasswordRestoreEntryDto $entryDto
    ): Response {
        $result = $handler->handle($entryDto);

        $resultDto = $this->apiDtoFactory->createApiDto(ChangePasswordRestoreResultDto::class, $result);

        return new ApiResponse($resultDto);
    }
}
