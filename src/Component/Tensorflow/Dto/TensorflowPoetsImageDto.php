<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Dto;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

class TensorflowPoetsImageDto implements DtoResolverInterface, TensorflowImageInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     */
    private $image;

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'image',
        ]);

        $resolver->setAllowedTypes('image', 'string');
    }
}
