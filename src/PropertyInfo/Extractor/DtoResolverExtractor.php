<?php

declare(strict_types=1);

namespace App\PropertyInfo\Extractor;

use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;

class DtoResolverExtractor implements PropertyListExtractorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getProperties($class, array $context = []): ?array
    {
        if (!is_subclass_of($class, DtoResolverInterface::class)) {
            return null;
        }

        /** @var DtoResolverInterface $object */
        $object = new $class();

        return $object->getDefinedProperties();
    }
}
