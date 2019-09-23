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
        $peoplePredictPhotoList = [];
        $peoplePredictSubscriberList = [];

        $subscriberPredictCount = $this->manager->getSubscriberPredictCount();

        foreach ($this->manager->getSubscriberPhotoList() as $subscriberId => $subscriberPhotos) {
            $photoIdList = explode(',', $subscriberPhotos['photo_ids']);
            $extensionList = explode(',', $subscriberPhotos['extensions']);

            $peoplePredictSubscriberPhotoList = $this->getPeoplePredictPhotoList($photoIdList, $extensionList);

            $peoplePredictPhotoList[] = $peoplePredictSubscriberPhotoList;
            $peoplePredictSubscriberList[$subscriberId] = $this->isSubscriberPeople($peoplePredictSubscriberPhotoList);

            $peoplePredictSubscriberCount = count($peoplePredictSubscriberList);

            $this->logger->debug("Predict people complete for $peoplePredictSubscriberCount/$subscriberPredictCount");
        }

        $peoplePredictPhotoList = array_replace(...$peoplePredictPhotoList);

        $this->manager->saveSubscriberPredict($peoplePredictSubscriberList);
        $this->manager->savePhotoPredict($peoplePredictPhotoList);
    }

    /**
     * @param array $subscriberPhotoIdList
     * @param array $extensionList
     *
     * @return bool[]
     *
     * @throws ExceptionInterface
     * @throws TensorflowException
     */
    private function getPeoplePredictPhotoList(array $subscriberPhotoIdList, array $extensionList): array
    {
        $peoplePredictPhotoList = [];

        foreach ($subscriberPhotoIdList as $key => $photoId) {
            $path = $this->pathGenerator->generateIntPath($photoId);
            $extension = $extensionList[$key];

            $imagePathname = "$this->photoPublicDir/$path/$photoId.$extension";

            $tensorflowPoetsImageDto = new TensorflowPoetsPredictDto([
                'classificationModel' => ClassificationEnum::PEOPLE,
                'image' => $imagePathname,
            ]);

            $predict = $this->tensorflowService->predict($tensorflowPoetsImageDto);

            $peoplePredictPhotoList[$photoId] = $predict === PeopleClassificationEnum::PEOPLE;
        }

        return $peoplePredictPhotoList;
    }

    /**
     * @param bool[] $peoplePredictPhotoList
     *
     * @return bool
     */
    private function isSubscriberPeople(array $peoplePredictPhotoList): bool
    {
        $countPeople = count(array_filter($peoplePredictPhotoList));
        $countUndefined = count($peoplePredictPhotoList) - $countPeople;

        return $countUndefined <= $countPeople;
    }
}
