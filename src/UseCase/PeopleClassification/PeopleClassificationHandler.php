<?php

declare(strict_types=1);

namespace App\UseCase\PeopleClassification;

use App\Component\PathGenerator\PathGenerator;
use App\Component\Tensorflow\Dto\TensorflowPoetsPredictDto;
use App\Component\Tensorflow\Enum\ClassificationEnum;
use App\Component\Tensorflow\Service\TensorflowService;
use Exception;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

class PeopleClassificationHandler
{
    public const LABEL_PEOPLE = 'people';

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
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function handle(): void
    {
        $photoPredictList = [];
        $imagePathnameMap = [];
        $subscriberPredictList = [];

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

                if ($label === self::LABEL_PEOPLE && $probability > 0.5) {
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

        $countGroupPeopleList = $this->manager->getPeopleSubscriberGroupCount();
        $this->manager->saveReportSubscriberPredictPeople($countGroupPeopleList);
    }
}
