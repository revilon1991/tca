<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Dto;

use App\Component\Tensorflow\Enum\ClassificationEnum;
use ReflectionException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

class TensorflowPoetsPredictDto implements DtoResolverInterface, TensorflowPredictInterface
{
    use DtoResolverTrait;

    /**
     * @var array
     */
    private $imageList;

    /**
     * @var string
     */
    private $classificationModel;

    /**
     * @return array
     */
    public function getImageList(): array
    {
        return $this->imageList;
    }

    /**
     * @return string
     */
    public function getClassificationModel(): string
    {
        return $this->classificationModel;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'imageList',
            'classificationModel',
        ]);

        $resolver->setAllowedTypes('imageList', 'array');

        $resolver->addAllowedValues('classificationModel', ClassificationEnum::getEnumList());
    }
}
