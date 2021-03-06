<?php

declare(strict_types=1);

namespace App\Entity;

use App\Component\IdGenerator\IdGenerator;
use App\Doctrine\Dbal\Type\SubscriberTypeEnumType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="uniqExternalId",
 *             columns={"externalId", "externalHash"}
 *         )
 *     }
 * )
 * @ORM\Entity()
 */
class Subscriber
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
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $externalId;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $externalHash;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $firstName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $lastName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $username;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $phone;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity=GroupSubscriber::class, mappedBy="group")
     */
    private $groupList;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity=Photo::class, mappedBy="subscriber", cascade={"persist", "remove"})
     */
    private $photoList;

    /**
     * @var string
     *
     * @ORM\Column(type=SubscriberTypeEnumType::NAME)
     */
    private $type;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $people;

    /**
     * @var User|null
     *
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="subscriber")
     */
    private $user;

    public function __construct()
    {
        $this->groupList = new ArrayCollection();
        $this->photoList = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     */
    public function setExternalId(string $externalId): void
    {
        $this->externalId = $externalId;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     */
    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     */
    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     */
    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     */
    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @param Group $group
     */
    public function addGroup(Group $group): void
    {
        if ($this->groupList->contains($group)) {
            return;
        }

        $this->groupList->add($group);

        $group->addSubscriber($this);
    }

    /**
     * @param Group $group
     */
    public function removeGroup(Group $group): void
    {
        if (!$this->groupList->contains($group)) {
            return;
        }

        $this->groupList->removeElement($group);

        $group->removeSubscriber($this);
    }

    /**
     * @return Collection
     */
    public function getPhotoList(): Collection
    {
        return $this->photoList;
    }

    /**
     * @param Photo $photo
     */
    public function addPhoto(Photo $photo): void
    {
        if ($this->photoList->contains($photo)) {
            return;
        }

        $this->photoList->add($photo);

        $photo->setSubscriber($this);
    }

    /**
     * @return Collection
     */
    public function getGroupList(): Collection
    {
        return $this->groupList;
    }

    /**
     * @return string
     */
    public function getExternalHash(): string
    {
        return $this->externalHash;
    }

    /**
     * @param string $externalHash
     */
    public function setExternalHash(string $externalHash): void
    {
        $this->externalHash = $externalHash;
    }

    /**
     * @return bool
     */
    public function isPeople(): bool
    {
        return $this->people;
    }

    /**
     * @param bool $people
     */
    public function setPeople(bool $people): void
    {
        $this->people = $people;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }
}
