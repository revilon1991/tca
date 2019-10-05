<?php

declare(strict_types=1);

namespace App\UseCase\PeopleClassification;

use App\Component\PathGenerator\PathGenerator;
use App\Component\Tensorflow\Dto\TensorflowPoetsPredictDto;
use App\Component\Tensorflow\Enum\ClassificationEnum;
use App\Component\Tensorflow\Exception\TensorflowException;
use App\Component\Tensorflow\Service\TensorflowService;
use App\Enum\PeopleClassificationEnum;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

class PeopleClassificationHandler
{
    /**
     * @var PeopleClassificationManager
     */
    private $manager;

    /**
     * @var TensorflowService
     */
    private $tensorflowService;

    /**
     * @var PathGenerator
     */
    private $pathGenerator;

    /**
     * @var string
     */
    private $photoPublicDir;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PeopleClassificationManager $manager
     * @param TensorflowService $tensorflowService
     * @param PathGenerator $pathGenerator
     * @param string $photoPublicDir
     * @param LoggerInterface $logger
     */
    public function __construct(
        PeopleClassificationManager $manager,
        TensorflowService $tensorflowService,
        PathGenerator $pathGenerator,
        string $photoPublicDir,
        LoggerInterface $logger
    ) {
        $this->manager = $manager;
        $this->tensorflowService = $tensorflowService;
        $this->pathGenerator = $pathGenerator;
        $this->photoPublicDir = $photoPublicDir;
        $this->logger = $logger;
    }

    /**
     * @throws DBALException
     * @throws ExceptionInterface
     * @throws TensorflowException
     */
    public function handle(): void
    {
        $peoplePredictSubscriberList = [];
        $peoplePredictPhotoList = [];
        $countGroupPeople = [];

        $subscriberPredictCount = $this->manager->getSubscriberPredictCount();

        foreach ($this->manager->getSubscriberPhotoList() as $subscriberId => $subscriberPhotos) {
            $groupIdList = explode(',', $subscriberPhotos['group_ids']);
            $photoNameList = explode(',', $subscriberPhotos['photo_names']);

            $peoplePredictSubscriberList[$subscriberId] = $this->isSubscriberPeople(
                $photoNameList,
                $peoplePredictPhotoList
            );

            if (!$peoplePredictSubscriberList[$subscriberId]) {
                foreach ($groupIdList as $groupId) {
                    isset($countGroupPeople[$groupId])
                        ? $countGroupPeople[$groupId]++
                        : $countGroupPeople[$groupId] = 1
                    ;
                }
            }

            $peoplePredictSubscriberCount = count($peoplePredictSubscriberList);
            $this->logger->debug("Predict people complete for $peoplePredictSubscriberCount/$subscriberPredictCount");
        }

        $this->manager->savePhotoPredictPeople($peoplePredictPhotoList);
        $this->manager->saveSubscriberPredictPeople($peoplePredictSubscriberList);
        $this->manager->saveReportSubscriberPredictPeople($countGroupPeople);
    }

    /**
     * @param array $photoNameList
     * @param array $photoList
     *
     * @return bool
     *
     * @throws ExceptionInterface
     * @throws TensorflowException
     */
    private function isSubscriberPeople(array $photoNameList, array &$photoList): bool
    {
        $peoplePredictPhotoList = [];

        foreach ($photoNameList as $photoName) {
            $photoId = rtrim($photoName, '.jpeg');

            $path = $this->pathGenerator->generateIntPath($photoId);

            $imagePathname = "$this->photoPublicDir/$path/$photoName";

            $tensorflowPoetsImageDto = new TensorflowPoetsPredictDto([
                'classificationModel' => ClassificationEnum::PEOPLE,
                'image' => $imagePathname,
            ]);

            $predict = $this->tensorflowService->predict($tensorflowPoetsImageDto);

            $photoList[$photoId] = $predict === PeopleClassificationEnum::PEOPLE;
            $peoplePredictPhotoList[] = $predict === PeopleClassificationEnum::PEOPLE;
        }

        $countPeople = count(array_filter($peoplePredictPhotoList));
        $countUndefined = count($peoplePredictPhotoList) - $countPeople;

        return $countUndefined <= $countPeople;
    }
}
