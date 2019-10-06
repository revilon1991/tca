<?php

declare(strict_types=1);

namespace App\UseCase\PeopleClassification;

use App\Component\PathGenerator\PathGenerator;
use App\Component\Tensorflow\Dto\TensorflowPoetsPredictDto;
use App\Component\Tensorflow\Enum\ClassificationEnum;
use App\Component\Tensorflow\Service\TensorflowService;
use App\Enum\PeopleClassificationEnum;
use Doctrine\DBAL\ConnectionException;
use Exception;
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
     * @throws ExceptionInterface
     * @throws ConnectionException
     * @throws Exception
     */
    public function handle(): void
    {
        $photoPredictList = [];
        $imagePathnameMap = [];
        $subscriberPredictList = [];

        $this->manager->beginTransaction();

        try {
            $this->manager->clearPeopleMark();

            foreach ($this->manager->getPhotoList() as $photo) {
                $path = $this->pathGenerator->generateIntPath($photo['id']);
                $imagePathnameMap[$photo['id']] = "$this->photoPublicDir/$path/$photo[id].$photo[extension]";
            }

            $predictList = $this->tensorflowService->predict(new TensorflowPoetsPredictDto([
                'classificationModel' => ClassificationEnum::PEOPLE,
                'imageList' => $imagePathnameMap,
            ]));

            foreach ($this->manager->getSubscriberPhotoList() as $subscriberId => $photoList) {
                $countPeople = 0;
                $countUndefined = 0;

                foreach ($photoList as $photoId) {
                    $predictKey = $imagePathnameMap[$photoId];
                    $label = $predictList[$predictKey]['label'];
                    $probability = $predictList[$predictKey]['probability'];

                    if ($label === PeopleClassificationEnum::PEOPLE && $probability > 0.5) {
                        $photoPredictList[$photoId] = true;
                        $countPeople++;
                    } else {
                        $photoPredictList[$photoId] = false;
                        $countUndefined++;
                    }
                }

                if ($countUndefined <= $countPeople) {
                    $subscriberPredictList[$subscriberId] = true;
                } else {
                    $subscriberPredictList[$subscriberId] = false;
                }
            }

            $this->manager->savePhotoPredictPeople($photoPredictList);
            $this->manager->saveSubscriberPredictPeople($subscriberPredictList);

            $countGroupPeople = $this->manager->getPeopleSubscriberGroupCount();
            $this->manager->saveReportSubscriberPredictPeople($countGroupPeople);

            $this->manager->commit();
        } catch (Exception $exception) {
            $this->manager->rollBack();

            $this->logger->error("Rollback while run people classification: {$exception->getMessage()}");

            throw $exception;
        }
    }
}
