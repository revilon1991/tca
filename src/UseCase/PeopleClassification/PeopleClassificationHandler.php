<?php

declare(strict_types=1);

namespace App\UseCase\PeopleClassification;

use App\Component\PathGenerator\PathGenerator;
use App\Component\Tensorflow\Dto\TensorflowPoetsImageDto;
use App\Component\Tensorflow\Exception\TensorflowException;
use App\Component\Tensorflow\Service\TensorflowService;
use App\Enum\ClassificationEnum;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @param PeopleClassificationManager $manager
     * @param TensorflowService $tensorflowService
     * @param PathGenerator $pathGenerator
     * @param string $photoPublicDir
     */
    public function __construct(
        PeopleClassificationManager $manager,
        TensorflowService $tensorflowService,
        PathGenerator $pathGenerator,
        string $photoPublicDir
    ) {
        $this->manager = $manager;
        $this->tensorflowService = $tensorflowService;
        $this->pathGenerator = $pathGenerator;
        $this->photoPublicDir = $photoPublicDir;
    }

    /**
     * @param OutputInterface $output
     *
     * @throws DBALException
     * @throws ExceptionInterface
     * @throws TensorflowException
     */
    public function handle(OutputInterface $output): void
    {
        $subscriberPredictList = [];

        $subscriberPredictCount = $this->manager->getSubscriberPredictCount();

        foreach ($this->manager->getSubscriberPhotoList() as $subscriberId => $subscriberPhotos) {
            $subscriberPhotoIdList = explode(',', $subscriberPhotos['photo_ids']);
            $extensionList = explode(',', $subscriberPhotos['extensions']);

            $subscriberPredictList[$subscriberId] = $this->isPeople($subscriberPhotoIdList, $extensionList);

            $predictCompleteCount = count($subscriberPredictList);

            $output->writeln("Predict complete for $predictCompleteCount/$subscriberPredictCount");
        }

        $this->manager->savePredict($subscriberPredictList);
    }

    /**
     * @param array $photoIdList
     * @param array $extensionList
     *
     * @return bool
     *
     * @throws ExceptionInterface
     * @throws TensorflowException
     */
    private function isPeople(array $photoIdList, array $extensionList): bool
    {
        $classificationList = [];

        foreach ($photoIdList as $key => $photoId) {
            $path = $this->pathGenerator->generateIntPath($photoId);
            $extension = $extensionList[$key];

            $imagePathname = "$this->photoPublicDir/$path/$photoId.$extension";

            $classificationList[] = $this->predict($imagePathname);
        }

        $classificationCountValueList = array_count_values($classificationList);

        if (!isset($classificationCountValueList[ClassificationEnum::PEOPLE])) {
            return false;
        }

        $countUndefined = $classificationCountValueList[ClassificationEnum::UNDEFINED] ?? 0;
        $countPeople = $classificationCountValueList[ClassificationEnum::UNDEFINED] ?? 0;

        return $countUndefined <= $countPeople;
    }

    /**
     * @param string $imagePathname
     *
     * @return string
     *
     * @throws ExceptionInterface
     * @throws TensorflowException
     */
    private function predict(string $imagePathname): string
    {
        if (!file_exists($imagePathname)) {
            return ClassificationEnum::UNDEFINED;
        }

        $tensorflowPoetsImageDto = new TensorflowPoetsImageDto(['image' => $imagePathname]);

        $predict = $this->tensorflowService->predict($tensorflowPoetsImageDto);

        if ($predict === ClassificationEnum::PEOPLE) {
            return ClassificationEnum::PEOPLE;
        }

        return ClassificationEnum::UNDEFINED;
    }
}
