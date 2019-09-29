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
        $peoplePredictSubscriberList = [];
        $countGroupMale = [];

        $subscriberPredictCount = $this->manager->getSubscriberPredictCount();

        foreach ($this->manager->getSubscriberPhotoList() as $subscriberId => $subscriberPhotos) {
            $groupIdList = explode(',', $subscriberPhotos['group_ids']);
            $photoNameList = explode(',', $subscriberPhotos['photo_names']);

            $peoplePredictSubscriberList[$subscriberId] = $this->getSubscriberMale($photoNameList);
            $predict = $peoplePredictSubscriberList[$subscriberId];

            foreach ($groupIdList as $groupId) {
                if (!isset($countGroupMale[$groupId][$predict])) {
                    $countGroupMale[$groupId][$predict] = 1;
                }

                $countGroupMale[$groupId][$predict]++;
            }

            $peoplePredictSubscriberCount = count($peoplePredictSubscriberList);
            $this->logger->debug("Predict people complete for $peoplePredictSubscriberCount/$subscriberPredictCount");
        }

        $this->manager->saveReportSubscriberPredictMale($countGroupMale);
    }

    /**
     * @param array $photoNameList
     *
     * @return string
     *
     * @throws ExceptionInterface
     * @throws TensorflowException
     */
    private function getSubscriberMale(array $photoNameList): string
    {
        $classificationList = [];

        foreach ($photoNameList as $photoName) {
            $photoName = rtrim($photoName, '.jpeg');

            $path = $this->pathGenerator->generateIntPath($photoName);

            $imagePathname = "$this->photoPublicDir/$path/$photoName";

            $tensorflowPoetsImageDto = new TensorflowPoetsPredictDto([
                'classificationModel' => ClassificationEnum::MALE,
                'image' => $imagePathname,
            ]);

            $classificationList[$photoName] = $this->tensorflowService->predict($tensorflowPoetsImageDto);
        }

        $classificationCountValueList = array_count_values($classificationList);

        $countMan = $classificationCountValueList[MaleClassificationEnum::MAN] ?? 0;
        $countWoman = $classificationCountValueList[MaleClassificationEnum::WOMAN] ?? 0;

        if ($countMan >= $countWoman) {
            return MaleClassificationEnum::MAN;
        }

        return MaleClassificationEnum::WOMAN;
    }
}
