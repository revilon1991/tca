<?php
namespace App\Doctrine\Dbal\Type;

use App\Enum\AbstractEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use ReflectionException;

abstract class AbstractSetType extends Type
{
    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function convertToDatabaseValue($valueList, AbstractPlatform $platform)
    {
        if (!is_array($valueList) || count($valueList) <= 0) {
            return '';
        }

        $notDefinedEnumList = array_diff($valueList, $this->getValues());

        if (count($notDefinedEnumList) > 0) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid value "%s". It is not defined in "%s"',
                    implode(',', $notDefinedEnumList),
                    static::class
                )
            );
        }

        return implode(',', $valueList);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return [];
        }

        return explode(',', $value);
    }

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
                $this->getValues()
            )
        );

        return sprintf('SET(%s)', $values);
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
    private function getValues(): array
    {
        /** @var AbstractEnum $enumClass */
        $enumClass = $this->getEnumClass();

        return array_keys($enumClass::getListCombine());
    }
}
