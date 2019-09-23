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
     * @var string
     */
    private $image;

    /**
     * @var string
     */
    private $classificationModel;

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
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
            'image',
            'classificationModel',
        ]);

        $resolver->setAllowedTypes('image', 'string');

        $resolver->addAllowedValues('classificationModel', ClassificationEnum::getEnumList());
    }
}
