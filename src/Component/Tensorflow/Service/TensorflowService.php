<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Service;

use App\Component\Tensorflow\Dto\TensorflowImageInterface;
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
     * @param TensorflowImageInterface $tensorflowImage
     *
     * @return string|null
     *
     * @throws TensorflowException
     */
    public function predict(TensorflowImageInterface $tensorflowImage): ?string
    {
        foreach ($this->providerList as $tensorflowProvider) {
            if ($tensorflowProvider->supports($tensorflowImage)) {
                return $tensorflowProvider->predict($tensorflowImage);
            }
        }

        $tensorflowImageClassName = get_class($tensorflowImage);

        throw new TensorflowException("Not find provider supported '$tensorflowImageClassName'");
    }

    /**
     * @param TensorflowImageInterface[] $tensorflowImageList
     *
     * @return array
     *
     * @throws TensorflowException
     */
    public function predictList(array $tensorflowImageList): array
    {
        $labelList = [];

        foreach ($tensorflowImageList as $key => $tensorflowImage) {
            $labelList[$key] = $this->predict($tensorflowImage);
        }

        return $labelList;
    }
}
