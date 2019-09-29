<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\GroupTypeEnum;
use DateTime;
use DateTimeZone;
use ReflectionException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

class ChannelInfoDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     */
    private $externalId;

    /**
     * @var string
     */
    private $externalHash;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string|null
     */
    private $about;

    /**
     * @var int
     */
    private $subscriberCount;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $photoMeta;

    /**
     * @var string
     */
    private $photoId;

    /**
     * @var string
     */
    private $photoHash;

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }

    /**
     * @return string
     */
    public function getExternalHash(): string
    {
        return $this->externalHash;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string|null
     */
    public function getAbout(): ?string
    {
        return $this->about;
    }

    /**
     * @return int
     */
    public function getSubscriberCount(): int
    {
        return $this->subscriberCount;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getPhotoMeta(): array
    {
        return $this->photoMeta;
    }

    /**
     * @return string
     */
    public function getPhotoExternalId(): string
    {
        return $this->photoId;
    }

    /**
     * @return string
     */
    public function getPhotoExternalHash(): string
    {
        return $this->photoHash;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'externalId',
            'externalHash',
            'title',
            'username',
            'about',
            'subscriberCount',
            'type',
            'photoMeta',
            'photoId',
            'photoHash',
        ]);

        $resolver->setNormalizer('externalId', function (Options $options, $value) {
            return (string)$value;
        });

        $resolver->setNormalizer('externalHash', function (Options $options, $value) {
            return (string)$value;
        });

        $resolver->setNormalizer('subscriberCount', function (Options $options, $value) {
            return (int)$value;
        });

        $resolver->setNormalizer('about', function (Options $options, $value) {
            return empty($value) ? null : (string)$value;
        });

        $resolver->setNormalizer('photoId', function (Options $options, $value) {
            return (string)$value;
        });

        $resolver->setNormalizer('photoHash', function (Options $options, $value) {
            return (string)$value;
        });

        $resolver->setAllowedValues('type', GroupTypeEnum::getList());
    }
}
