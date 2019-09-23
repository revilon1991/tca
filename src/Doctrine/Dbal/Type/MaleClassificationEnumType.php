<?php

namespace App\Doctrine\Dbal\Type;

use App\Enum\MaleClassificationEnum;

class MaleClassificationEnumType extends AbstractEnumType
{
    public const NAME = 'male_classification_enum';

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
        return MaleClassificationEnum::class;
    }
}
