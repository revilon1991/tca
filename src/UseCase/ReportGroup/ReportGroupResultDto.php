<?php

declare(strict_types=1);

namespace App\UseCase\ReportGroup;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverInterface;
use Wakeapp\Component\DtoResolver\Dto\DtoResolverTrait;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     type="object",
 *     required={
 *         "groupId",
 *         "countSubscriber",
 *         "countMan",
 *         "countWoman",
 *         "date",
 *     }
 * )
 */
class ReportGroupResultDto implements DtoResolverInterface
{
    use DtoResolverTrait;

    /**
     * @var string
     *
     * @SWG\Property(type="string", example=1234567890)
     */
    private $groupId;

    /**
     * @var int
     *
     * @SWG\Property(type="integer", description="Total subscribers", example=600)
     */
    private $countSubscriber;

    /**
     * @var int
     *
     * @SWG\Property(type="integer", description="Percent man of the total subscribers", example=40)
     */
    private $countMan;

    /**
     * @var int
     *
     * @SWG\Property(type="integer", description="Percent woman of the total subscribers", example=60)
     */
    private $countWoman;

    /**
     * @var int
     *
     * @SWG\Property(type="integer", description="Unix date analytics group", example=1572296400)
     */
    private $date;
}
