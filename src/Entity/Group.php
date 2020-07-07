<?php

declare(strict_types=1);

namespace App\Entity;

use App\Component\IdGenerator\IdGenerator;
use App\Doctrine\Dbal\Type\GroupTypeEnumType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Table(
 *     name="`group`",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="uniqExternalId",
 *             columns={"externalId", "externalHash"}
 *         )
 *     })
 * @ORM\Entity()
 */
class Group
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
     * @var string
     *
     * @ORM\Column(type=GroupTypeEnumType::NAME)
     */
    private $type;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity=GroupSubscriber::class, mappedBy="group")
     */
    private $subscriberList;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $username;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $about;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity=Photo::class, mappedBy="group", cascade={"persist", "remove"})
     */
    private $photoList;

    public function __construct()
    {
        $this->subscriberList = new ArrayCollection();
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
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string|null
     */
    public function getAbout(): ?string
    {
        return $this->about;
    }

    /**
     * @param string $about
     */
    public function setAbout(string $about): void
    {
        $this->about = $about;
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
    public function addPhotoList(Photo $photo): void
    {
        if ($this->photoList->contains($photo)) {
            return;
        }

        $this->photoList->add($photo);

        $photo->setGroup($this);
    }

    /**
     * @param Subscriber $subscriber
     */
    public function addSubscriber(Subscriber $subscriber): void
    {
        if ($this->subscriberList->contains($subscriber)) {
            return;
        }

        $this->subscriberList->add($subscriber);

        $subscriber->addGroup($this);
    }

    /**
     * @param Subscriber $subscriber
     */
    public function removeSubscriber(Subscriber $subscriber): void
    {
        if (!$this->subscriberList->contains($subscriber)) {
            return;
        }

        $this->subscriberList->removeElement($subscriber);
    }

    /**
     * @return Collection
     */
    public function getSubscriberList(): Collection
    {
        return $this->subscriberList;
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
}
