<?php

declare(strict_types=1);

namespace App\Controller;

use App\Component\JwtToken\Exception\JwtTokenException;
use App\Dto\ApiDocResultDto;
use App\UseCase\EditUser\EditUserEntryDto;
use App\UseCase\EditUser\EditUserHandler;
use App\UseCase\EditUser\EditUserResultDto;
use App\UseCase\EditUserForm\EditUserFormHandler;
use Doctrine\DBAL\DBALException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Wakeapp\Bundle\ApiPlatformBundle\Factory\ApiDtoFactory;
use Wakeapp\Bundle\ApiPlatformBundle\HttpFoundation\ApiResponse;

/**
 * @SWG\Tag(name="User")
 * @SWG\Response(response="default", @Model(type=ApiDocResultDto::class), description="Response wrapper")
 */
class UserController extends AbstractController
{
    /**
     * @var ApiDtoFactory
     */
    private $apiDtoFactory;

    /**
     * @param ApiDtoFactory $apiDtoFactory
     */
    public function __construct(ApiDtoFactory $apiDtoFactory)
    {
        $this->apiDtoFactory = $apiDtoFactory;
    }

    /**
     * @Route(path="/cabinet/edit_user", methods={"GET"})
     *
     * @param EditUserFormHandler $handler
     *
     * @return Response
     */
    public function editUserForm(EditUserFormHandler $handler): Response
    {
        $handler->handle();

        return $this->render('User/edit.html.twig');
    }

    /**
     * @Route(path="/cabinet/api/edit_user", methods={"POST"})
     *
     * @SWG\Parameter(name="body", in="body", @Model(type=EditUserEntryDto::class), required=true)
     * @SWG\Response(
     *      response=ApiResponse::HTTP_OK,
     *      description="Successful result in 'data' offset",
     *      @Model(type=EditUserResultDto::class)
     * )
     *
     * @param EditUserHandler $handler
     * @param EditUserEntryDto $entryDto
     *
     * @return Response
     *
     * @throws JwtTokenException
     * @throws DBALException
     */
    public function editUser(EditUserHandler $handler, EditUserEntryDto $entryDto): Response
    {
        $result = $handler->handle($entryDto);

        $resultDto = $this->apiDtoFactory->createApiDto(EditUserResultDto::class, $result);

        return new ApiResponse($resultDto);
    }
}
