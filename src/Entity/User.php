<?php

declare(strict_types=1);

namespace App\Entity;

use App\Component\IdGenerator\IdGenerator;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Serializable;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Table(
 *     name="`user`",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="uniqSubscriberExternalId",
 *             columns={"subscriberExternalId"}
 *         ),
 *         @ORM\UniqueConstraint(
 *             name="uniqUsername",
 *             columns={"username"}
 *         )
 *     }
 * )
 * @ORM\Entity()
 */
class User implements UserInterface, Serializable
{
    use TimestampableEntity;

    /**
     * @var string
     *
     * @ORM\Column(type="bigint")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=IdGenerator::class)
     */
    private $id;

    /**
     * @var Subscriber
     *
     * @ORM\OneToOne(targetEntity=Subscriber::class, inversedBy="user")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true)
     */
    private $subscriber;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $password;

    /**
     * @var array
     *
     * @ORM\Column(type="json", nullable=true)
     */
    private $roles = [];

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $botHash;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $subscriberExternalId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $actualUserAgent;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $actualLoginTime;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $actualIp;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $referer;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $email;

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized): void
    {
        [
            $this->id,
            $this->username,
            $this->password,
        ] = unserialize($serialized, ['allowed_classes' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * @return string
     */
    public function getBotHash(): string
    {
        return $this->botHash;
    }

    /**
     * @param string $botHash
     */
    public function setBotHash(string $botHash): void
    {
        $this->botHash = $botHash;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @param Subscriber $subscriber
     */
    public function setSubscriber(Subscriber $subscriber): void
    {
        $this->subscriber = $subscriber;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * @param array $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @param string $subscriberExternalId
     */
    public function setSubscriberExternalId(string $subscriberExternalId): void
    {
        $this->subscriberExternalId = $subscriberExternalId;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Subscriber
     */
    public function getSubscriber(): Subscriber
    {
        return $this->subscriber;
    }

    /**
     * @return string
     */
    public function getSubscriberExternalId(): string
    {
        return $this->subscriberExternalId;
    }

    /**
     * @return string|null
     */
    public function getActualUserAgent(): ?string
    {
        return $this->actualUserAgent;
    }

    /**
     * @param string|null $actualUserAgent
     */
    public function setActualUserAgent(?string $actualUserAgent): void
    {
        $this->actualUserAgent = $actualUserAgent;
    }

    /**
     * @return DateTime|null
     */
    public function getActualLoginTime(): ?DateTime
    {
        return $this->actualLoginTime;
    }

    /**
     * @param DateTime|null $actualLoginTime
     */
    public function setActualLoginTime(?DateTime $actualLoginTime): void
    {
        $this->actualLoginTime = $actualLoginTime;
    }

    /**
     * @return string|null
     */
    public function getActualIp(): ?string
    {
        return $this->actualIp;
    }

    /**
     * @param string|null $actualIp
     */
    public function setActualIp(?string $actualIp): void
    {
        $this->actualIp = $actualIp;
    }

    /**
     * @return string|null
     */
    public function getReferer(): ?string
    {
        return $this->referer;
    }

    /**
     * @param string|null $referer
     */
    public function setReferer(?string $referer): void
    {
        $this->referer = $referer;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
