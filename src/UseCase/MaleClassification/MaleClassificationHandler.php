<?php

declare(strict_types=1);

namespace App\UseCase\MaleClassification;

use App\Component\PathGenerator\PathGenerator;
use App\Component\Tensorflow\Dto\TensorflowPoetsPredictDto;
use App\Component\Tensorflow\Enum\ClassificationEnum;
use App\Component\Tensorflow\Exception\TensorflowException;
use App\Component\Tensorflow\Service\TensorflowService;
use App\Enum\MaleClassificationEnum;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

class MaleClassificationHandler
{
    /**
     * @var MaleClassificationManager
     */
    private $manager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PathGenerator
     */
    private $pathGenerator;

    /**
     * @var string
     */
    private $photoPublicDir;

    /**
     * @var TensorflowService
     */
    private $tensorflowService;

    /**
     * @param MaleClassificationManager $manager
     * @param LoggerInterface $logger
     * @param PathGenerator $pathGenerator
     * @param string $photoPublicDir
     * @param TensorflowService $tensorflowService
     */
    public function __construct(
        MaleClassificationManager $manager,
        LoggerInterface $logger,
        PathGenerator $pathGenerator,
        string $photoPublicDir,
        TensorflowService $tensorflowService
    ) {
        $this->manager = $manager;
        $this->logger = $logger;
        $this->pathGenerator = $pathGenerator;
        $this->photoPublicDir = $photoPublicDir;
        $this->tensorflowService = $tensorflowService;
    }

    /**
     * @throws DBALException
     * @throws ExceptionInterface
     * @throws TensorflowException
     */
    public function handle(): void
    {
        $subscriberPredictList = [];

        $subscriberPredictCount = $this->manager->getSubscriberPredictCount();

        foreach ($this->manager->getSubscriberPhotoList() as $subscriberId => $subscriberPhotos) {
            $photoIdList = explode(',', $subscriberPhotos['photo_ids']);
            $extensionList = explode(',', $subscriberPhotos['extensions']);

            $subscriberPhotoPredictList = $this->getPhotoMale($photoIdList, $extensionList);
            $subscriberPredictList[$subscriberId] = $this->getSubscriberMale($subscriberPhotoPredictList);

            $predictCompleteCount = count($subscriberPredictList);

            $this->logger->debug("Predict male complete for $predictCompleteCount/$subscriberPredictCount");
        }

        $this->manager->savePredict($subscriberPredictList);
    }

    /**
     * @param array $subscriberPhotoPredictList
     *
     * @return string
     */
    private function getSubscriberMale(array $subscriberPhotoPredictList): string
    {
        $classificationCountValueList = array_count_values($subscriberPhotoPredictList);

        $countMan = $classificationCountValueList[MaleClassificationEnum::MAN] ?? 0;
        $countWoman = $classificationCountValueList[MaleClassificationEnum::WOMAN] ?? 0;

        if ($countMan >= $countWoman) {
            return MaleClassificationEnum::MAN;
        }

        return MaleClassificationEnum::WOMAN;
    }

    /**
     * @param array $photoIdList
     * @param array $extensionList
     *
     * @return array
     *
     * @throws ExceptionInterface
     * @throws TensorflowException
     */
    private function getPhotoMale(array $photoIdList, array $extensionList): array
    {
        $classificationList = [];

        foreach ($photoIdList as $key => $photoId) {
            $path = $this->pathGenerator->generateIntPath($photoId);
            $extension = $extensionList[$key];

            $imagePathname = "$this->photoPublicDir/$path/$photoId.$extension";

            $tensorflowPoetsImageDto = new TensorflowPoetsPredictDto([
                'classificationModel' => ClassificationEnum::MALE,
                'image' => $imagePathname,
            ]);

            $classificationList[$photoId] = $this->tensorflowService->predict($tensorflowPoetsImageDto);
        }

        return $classificationList;
    }
}
