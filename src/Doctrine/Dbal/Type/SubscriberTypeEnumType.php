<?php

namespace App\Doctrine\Dbal\Type;

use App\Enum\SubscriberTypeEnum;

class SubscriberTypeEnumType extends AbstractEnumType
{
    public const NAME = 'subscriber_type_enum';

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
        return SubscriberTypeEnum::class;
    }
}
