<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\Dbal\Type\SubscriberRoleEnumType;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="uniqGroupSubscriberId",
 *             columns={"groupId", "subscriberId"}
 *         )
 *     })
 * @ORM\Entity()
 */
class GroupSubscriber
{
    /**
     * @var Group
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=Group::class, inversedBy="groupList")
     * @ORM\JoinColumn(nullable=false)
     */
    private $group;

    /**
     * @var Subscriber
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=Subscriber::class, inversedBy="subscriberList")
     * @ORM\JoinColumn(nullable=false)
     */
    private $subscriber;

    /**
     * @var string
     *
     * @ORM\Column(type=SubscriberRoleEnumType::NAME, nullable=true)
     */
    private $role;

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
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole(string $role): void
    {
        $this->role = $role;
    }
}
