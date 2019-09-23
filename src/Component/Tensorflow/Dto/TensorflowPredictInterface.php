<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Dto;

interface TensorflowPredictInterface
{
    /**
     * Pathname to image for predict class
     *
     * @return string
     */
    public function getImage(): string;

    /**
     * Choose model for predict class
     *
     * @return string
     */
    public function getClassificationModel(): string;
}
