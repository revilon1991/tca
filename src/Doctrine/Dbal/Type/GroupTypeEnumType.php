<?php

namespace App\Doctrine\Dbal\Type;

use App\Enum\GroupTypeEnum;

class GroupTypeEnumType extends AbstractEnumType
{
    public const NAME = 'group_type_enum';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumClass(): string
    {
        return GroupTypeEnum::class;
    }
}
