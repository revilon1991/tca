<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Provider;

use App\Component\Tensorflow\Dto\TensorflowPredictInterface;

interface TensorflowProviderInterface
{
    /**
     * Build model
     *
     * @param string $classificationModel
     *
     * @return string
     */
    public function retrain(string $classificationModel): string;

    /**
     * Classification people image pathname
     *
     * @param TensorflowPredictInterface $image
     *
     * @return array
     */
    public function predict(TensorflowPredictInterface $image): array;

    /**
     * @param TensorflowPredictInterface $image
     *
     * @return bool
     */
    public function supports(TensorflowPredictInterface $image): bool;
}
