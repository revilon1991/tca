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
 *             columns={"date", "group_id"}
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
}
