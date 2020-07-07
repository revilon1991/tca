<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Group;
use App\Enum\GroupTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use function random_int;

class GroupFixtures extends Fixture
{
    public const REF_GROUP_FIRST = 'awesome';
    public const REF_GROUP_SECOND = 'foo';
    public const REF_GROUP_THIRD = 'bar';

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        foreach ([
            self::REF_GROUP_FIRST,
            self::REF_GROUP_SECOND,
            self::REF_GROUP_THIRD,
        ] as $reference) {
            $externalHash = (string)random_int(1000000000, 9000000000);
            $externalId = (string)random_int(1000000000000000000, 9000000000000000000);

            $group = new Group();
            $group->setUsername($reference);
            $group->setAbout("This $reference channel");
            $group->setTitle(ucfirst($reference));
            $group->setExternalHash($externalHash);
            $group->setExternalId($externalId);
            $group->setType(GroupTypeEnum::CHANNEL);

            $manager->persist($group);

            $this->addReference($reference, $group);
        }

        $manager->flush();
    }
}
