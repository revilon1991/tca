<?php

declare(strict_types=1);

namespace App\Validator;

use EXSyst\Component\Swagger\Schema;
use Linkin\Bundle\SwaggerResolverBundle\Validator\SwaggerValidatorInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class EmailSwaggerValidator implements SwaggerValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Schema $propertySchema, array $context = []): bool
    {
        return $propertySchema->getFormat() === 'email';
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Schema $propertySchema, string $propertyName, $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidOptionsException("Value '$value' is not correct email format");
        }
    }
}
