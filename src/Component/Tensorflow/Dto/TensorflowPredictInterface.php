<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Dto;

interface TensorflowPredictInterface
{
    /**
     * Pathname to image list for predict class
     *
     * @return array
     */
    public function getImageList(): array;

    /**
     * Choose model for predict class
     *
     * @return string
     */
    public function getClassificationModel(): string;
}
