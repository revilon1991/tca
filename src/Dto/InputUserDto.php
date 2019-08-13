<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

class InputUserDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var int
     */
    private $accessHash;

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'userId',
            'accessHash',
        ]);

        $resolver->setNormalizer('userId', static function (Options $options, $value) {
            if (empty($value)) {
                throw new InvalidOptionsException('User id is empty.');
            }

            return (int)$value;
        });

        $resolver->setNormalizer('accessHash', static function (Options $options, $value) {
            if (empty($value)) {
                throw new InvalidOptionsException('Access hash id is empty.');
            }

            return (int)$value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(bool $onlyDefinedData = true): array
    {
        return [
            '_' => 'inputUser',
            'user_id' => $this->userId,
            'access_hash' => $this->accessHash,
        ];
    }
}
