<?php

namespace App\DataFixtures;

use App\Entity\Group;
use App\Entity\ReportGroup;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use function random_int;

class ReportGroupFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        foreach ([
            GroupFixtures::REF_GROUP_FIRST,
            GroupFixtures::REF_GROUP_SECOND,
            GroupFixtures::REF_GROUP_THIRD,
        ] as $referenceGroup) {
            /** @var Group $group */
            $group = $this->getReference($referenceGroup);

            for ($i = 1; $i <= 7; $i++) {
                $date = new DateTime("-$i day");
                $countMan = random_int(100, 500);
                $countWoman = random_int(100, 500);
                $countPeople = $countMan + $countWoman;
                $countRealSubscriber = $countPeople + random_int(100, 500);
                $countSubscriber = $countRealSubscriber + random_int(100, 500);

                $reportGroup = new ReportGroup();
                $reportGroup->setDate($date);
                $reportGroup->setCountMan($countMan);
                $reportGroup->setCountWoman($countWoman);
                $reportGroup->setCountPeople($countPeople);
                $reportGroup->setCountRealSubscriber($countRealSubscriber);
                $reportGroup->setCountSubscriber($countSubscriber);
                $reportGroup->setGroup($group);

                $manager->persist($reportGroup);
            }
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            GroupFixtures::class,
        ];
    }
}
