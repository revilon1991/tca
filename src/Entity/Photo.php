<?php

declare(strict_types=1);

namespace App\Entity;

use App\Component\IdGenerator\IdGenerator;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="uniqExternalId",
 *             columns={"external_id", "external_hash"}
 *         )
 *     }
 * )
 * @ORM\Entity()
 */
class Photo
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
     * @var Subscriber
     *
     * @ORM\ManyToOne(targetEntity=Subscriber::class, inversedBy="photoList")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true)
     */
    private $subscriber;

    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity=Group::class, inversedBy="photoList")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true)
     */
    private $group;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $extension;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $people;

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
     * @return Subscriber
     */
    public function getSubscriber(): Subscriber
    {
        return $this->subscriber;
    }

    /**
     * @param Subscriber $subscriber
     */
    public function setSubscriber(Subscriber $subscriber): void
    {
        $this->subscriber = $subscriber;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @param Group $group
     */
    public function setGroup(Group $group): void
    {
        $this->group = $group;
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
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
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
}
