<?php

declare(strict_types=1);

namespace App\UseCase\ReportGroup;

use Wakeapp\Component\DtoResolver\Dto\CollectionDtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\CollectionDtoResolverTrait;

class ReportGroupCollectionResultDto implements CollectionDtoResolverInterface
{
    use CollectionDtoResolverTrait;

    /**
     * {@inheritdoc}
     */
    public static function getItemDtoClassName(): string
    {
        return ReportGroupResultDto::class;
    }
}
