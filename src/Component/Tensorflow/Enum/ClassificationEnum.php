<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Enum;

use ReflectionClass;
use ReflectionException;

class ClassificationEnum
{
    public const PEOPLE = 'people';
    public const MALE = 'male';

    /**
     * @return array
     *
     * @throws ReflectionException
     */
    public static function getEnumList(): array
    {
        $class = new ReflectionClass(__CLASS__);

        return $class->getConstants();
    }
}
