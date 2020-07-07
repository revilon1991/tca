<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ApiDocResultDto;
use App\UseCase\Dashboard\DashboardHandler;
use App\UseCase\ReportGroup\ReportGroupCollectionResultDto;
use App\UseCase\ReportGroup\ReportGroupEntryDto;
use App\UseCase\ReportGroup\ReportGroupHandler;
use App\UseCase\ReportGroup\ReportGroupResultDto;
use Doctrine\DBAL\DBALException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Wakeapp\Bundle\ApiPlatformBundle\Factory\ApiDtoFactory;
use Wakeapp\Bundle\ApiPlatformBundle\HttpFoundation\ApiResponse;

/**
 * @SWG\Tag(name="Report")
 * @SWG\Response(response="default", @Model(type=ApiDocResultDto::class), description="Response wrapper")
 */
class ReportController extends AbstractController
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
     * @Route(path="/cabinet/api/report_group", methods={"GET"})
     *
     * @SWG\Parameter(
     *     in="query",
     *     name="groupId",
     *     required=true,
     *     type="string",
     * )
     * @SWG\Response(
     *      response=ApiResponse::HTTP_OK,
     *      description="Successful result in 'data' offset",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=ReportGroupResultDto::class)),
     *      )
     * )
     *
     * @param ReportGroupEntryDto $entryDto
     * @param ReportGroupHandler $handler
     *
     * @return ApiResponse
     *
     * @throws DBALException
     */
    public function getReportGroup(ReportGroupEntryDto $entryDto, ReportGroupHandler $handler): ApiResponse
    {
        $resultList = $handler->handle($entryDto);

        $resultCollectionDto = $this->apiDtoFactory->createApiCollectionDto(ReportGroupCollectionResultDto::class);

        array_map([$resultCollectionDto, 'add'], $resultList);

        return new ApiResponse($resultCollectionDto);
    }

    /**
     * @Route(path="/cabinet/dashboard", methods={"GET"})
     *
     * @param DashboardHandler $handler
     *
     * @return Response
     *
     * @throws DBALException
     */
    public function dashboard(DashboardHandler $handler): Response
    {
        $initialState = $handler->handle();

        return $this->render('Report/dashboard.html.twig', [
            'initial_state' => json_encode($initialState),
        ]);
    }
}
