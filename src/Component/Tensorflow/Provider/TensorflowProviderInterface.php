<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Provider;

use App\Component\Tensorflow\Dto\TensorflowImageInterface;

interface TensorflowProviderInterface
{
    /**
     * Build model
     *
     * @return string
     */
    public function retrain(): string;

    /**
     * Classification image pathname
     *
     * @param TensorflowImageInterface $image
     *
     * @return string|null
     */
    public function predict(TensorflowImageInterface $image): ?string;

    /**
     * @param TensorflowImageInterface $image
     *
     * @return bool
     */
    public function supports(TensorflowImageInterface $image): bool;
}
