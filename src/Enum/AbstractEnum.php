<?php

namespace App\Enum;

use ReflectionClass;
use ReflectionException;

abstract class AbstractEnum
{
    /**
     * @var array
     */
    protected static $constantList;

    /**
     * @return array
     *
     * @throws ReflectionException
     */
    public static function getList(): array
    {
        $currentClassName = static::class;

        if (empty(static::$constantList[$currentClassName])) {
            $class = new ReflectionClass($currentClassName);

            static::$constantList[$currentClassName] = $class->getConstants();
        }

        return static::$constantList[$currentClassName];
    }

    /**
     * @return array
     *
     * @throws ReflectionException
     */
    public static function getListCombine(): array
    {
        return array_combine(static::getList(), static::getList());
    }

    /**
     * @param string $value
     *
     * @return int
     *
     * @throws ReflectionException
     */
    public static function getBit($value): int
    {
        $constants = static::getList();

        $index = 0;

        foreach ($constants as $constant) {
            if (mb_strtolower($constant) === mb_strtolower($value)) {
                return 1 << $index;
            }

            $index++;
        }

        return 0;
    }
}
