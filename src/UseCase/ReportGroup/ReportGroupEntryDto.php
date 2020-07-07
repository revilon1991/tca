<?php

declare(strict_types=1);

namespace App\UseCase\ReportGroup;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

class ReportGroupEntryDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     */
    private $groupId;

    /**
     * @return string
     */
    public function getGroupId(): string
    {
        return $this->groupId;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined([
            'groupId',
        ]);
    }
}
