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
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

class MaleClassificationHandler
{
    /**
     * @var MaleClassificationManager
     */
    private $manager;

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
     * @param PathGenerator $pathGenerator
     * @param string $photoPublicDir
     * @param TensorflowService $tensorflowService
     */
    public function __construct(
        MaleClassificationManager $manager,
        PathGenerator $pathGenerator,
        string $photoPublicDir,
        TensorflowService $tensorflowService
    ) {
        $this->manager = $manager;
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
        $manEnum = MaleClassificationEnum::MAN;
        $womanEnum = MaleClassificationEnum::WOMAN;

        $countMaleList = [];
        $imagePathnameMap = [];

        foreach ($this->manager->getPhotoList() as $photo) {
            $path = $this->pathGenerator->generateIntPath($photo['id']);
            $imagePathnameMap[$photo['id']] = "$this->photoPublicDir/$path/$photo[id].$photo[extension]";
        }

        $predictList = $this->tensorflowService->predict(new TensorflowPoetsPredictDto([
            'classificationModel' => ClassificationEnum::MALE,
            'imageList' => $imagePathnameMap,
        ]));

        foreach ($this->manager->getSubscriberPhotoList() as $groupId => $subscriberList) {
            $countMaleList[$groupId][$manEnum] = $countMaleList[$groupId][$manEnum] ?? 0;
            $countMaleList[$groupId][$womanEnum] = $countMaleList[$groupId][$womanEnum] ?? 0;

            foreach ($subscriberList as $photoList) {
                $subscriberPredictList = [];

                foreach ($photoList as $photoId) {
                    $predictKey = $imagePathnameMap[$photoId];

                    $subscriberPredictList[] = $predictList[$predictKey]['label'];
                }

                $predictCount = array_count_values($subscriberPredictList);

                $predictCount[$manEnum] = $predictCount[$manEnum] ?? 0;
                $predictCount[$womanEnum] = $predictCount[$womanEnum] ?? 0;

                if ($predictCount[$manEnum] > $predictCount[$womanEnum]) {
                    $countMaleList[$groupId][$manEnum]++;
                } else {
                    $countMaleList[$groupId][$womanEnum]++;
                }
            }
        }

        $this->manager->saveReportSubscriberPredictMale($countMaleList);
    }
}
