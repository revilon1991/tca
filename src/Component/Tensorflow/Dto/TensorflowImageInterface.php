<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Dto;

interface TensorflowImageInterface
{
    /**
     * Pathname to image for predict class
     *
     * @return string
     */
    public function getImage(): string;
}
