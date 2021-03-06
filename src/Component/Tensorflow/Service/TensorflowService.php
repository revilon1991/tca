<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Service;

use App\Component\Tensorflow\Dto\TensorflowPredictInterface;
use App\Component\Tensorflow\Exception\TensorflowException;
use App\Component\Tensorflow\Provider\TensorflowProviderInterface;
use function get_class;

class TensorflowService
{
    /**
     * @var TensorflowProviderInterface[]
     */
    private $providerList;

    /**
     * @param TensorflowProviderInterface[] $providerList
     */
    public function __construct(iterable $providerList)
    {
        $this->providerList = $providerList;
    }

    /**
     * @param TensorflowPredictInterface $tensorflowImage
     *
     * @return array
     *
     * @throws TensorflowException
     */
    public function predict(TensorflowPredictInterface $tensorflowImage): array
    {
        foreach ($this->providerList as $tensorflowProvider) {
            if ($tensorflowProvider->supports($tensorflowImage)) {
                return $tensorflowProvider->predict($tensorflowImage);
            }
        }

        $tensorflowImageClassName = get_class($tensorflowImage);

        throw new TensorflowException("Not find provider supported '$tensorflowImageClassName'");
    }
}
