<?php

declare(strict_types=1);

namespace App\Entity;

use App\Component\IdGenerator\IdGenerator;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="uniqPredictDateGroup",
 *             columns={"date", "groupId"}
 *         )
 *     }
 * )
 * @ORM\Entity()
 */
class ReportGroup
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
     * @var DateTime
     *
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity=Group::class)
     * @ORM\JoinColumn(referencedColumnName="id", nullable=false)
     */
    private $group;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $countSubscriber;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $countRealSubscriber;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $countPeople;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $countMan;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $countWoman;

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
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
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
     * @return int
     */
    public function getCountSubscriber(): int
    {
        return $this->countSubscriber;
    }

    /**
     * @param int $countSubscriber
     */
    public function setCountSubscriber(int $countSubscriber): void
    {
        $this->countSubscriber = $countSubscriber;
    }

    /**
     * @return int
     */
    public function getCountRealSubscriber(): int
    {
        return $this->countRealSubscriber;
    }

    /**
     * @param int $countRealSubscriber
     */
    public function setCountRealSubscriber(int $countRealSubscriber): void
    {
        $this->countRealSubscriber = $countRealSubscriber;
    }

    /**
     * @return int
     */
    public function getCountPeople(): int
    {
        return $this->countPeople;
    }

    /**
     * @param int $countPeople
     */
    public function setCountPeople(int $countPeople): void
    {
        $this->countPeople = $countPeople;
    }

    /**
     * @return int
     */
    public function getCountMan(): int
    {
        return $this->countMan;
    }

    /**
     * @param int $countMan
     */
    public function setCountMan(int $countMan): void
    {
        $this->countMan = $countMan;
    }

    /**
     * @return int
     */
    public function getCountWoman(): int
    {
        return $this->countWoman;
    }

    /**
     * @param int $countWoman
     */
    public function setCountWoman(int $countWoman): void
    {
        $this->countWoman = $countWoman;
    }
}
