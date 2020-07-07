<?php

declare(strict_types=1);

namespace App\Doctrine\Dbal\Type;

use App\Enum\SubscriberRoleEnum;

class SubscriberRoleEnumType extends AbstractEnumType
{
    public const NAME = 'subscriber_role_enum';

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
        return SubscriberRoleEnum::class;
    }
}
