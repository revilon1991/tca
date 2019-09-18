<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\SubscriberTypeEnum;
use DateTime;
use DateTimeZone;
use ReflectionException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;

class UserInfoDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     */
    private $externalId;

    /**
     * @var string
     */
    private $accessHash;

    /**
     * @var string
     */
    private $type;

    /**
     * @var DateTime
     */
    private $lastUpdate;

    /**
     * @var string|null
     */
    private $firstName;

    /**
     * @var string|null
     */
    private $lastName;

    /**
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $about;

    /**
     * @var string|null
     */
    private $phone;

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
    public function getAccessHash(): string
    {
        return $this->accessHash;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return DateTime
     */
    public function getLastUpdate(): DateTime
    {
        return $this->lastUpdate;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
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
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
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
            'accessHash',
            'type',
            'lastUpdate',
        ]);

        $resolver->setDefined([
            'firstName',
            'lastName',
            'username',
            'about',
            'phone',
        ]);

        $resolver->setNormalizer('lastUpdate', static function (Options $options, $value) {
            $dateTimeZone = new DateTimeZone(date_default_timezone_get());

            return (new DateTime())->setTimezone($dateTimeZone)->setTimestamp($value);
        });

        $resolver->setNormalizer('externalId', function (Options $options, $value) {
            return (string)$value;
        });
        $resolver->setNormalizer('accessHash', function (Options $options, $value) {
            return (string)$value;
        });

        $resolver->setAllowedValues('type', SubscriberTypeEnum::getList());
    }
}
