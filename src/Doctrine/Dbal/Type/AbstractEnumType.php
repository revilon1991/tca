<?php

namespace App\Doctrine\Dbal\Type;

use App\Enum\AbstractEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use ReflectionException;
use function in_array;

abstract class AbstractEnumType extends Type
{
    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $values = implode(
            ', ',
            array_map(
                function ($value) {
                    return sprintf("'%s'", $value);
                },
                $this->getValueList()
            )
        );

        return sprintf('ENUM(%s)', $values);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        if ($value && !in_array($value, $this->getValueList(), true)) {
            throw new InvalidArgumentException(sprintf('Invalid "%s" value for enum %s.', $value, $this->getName()));
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    /**
     * @return string
     */
    abstract protected function getEnumClass(): string;

    /**
     * @return array
     *
     * @throws ReflectionException
     */
    private function getValueList(): array
    {
        /** @var AbstractEnum $enumClass */
        $enumClass = $this->getEnumClass();

        return $enumClass::getList();
    }
}
