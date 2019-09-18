<?php

declare(strict_types=1);

namespace App\UseCase\PeopleClassification;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Generator;

class PeopleClassificationManager
{
    private const UPDATE_CHUNK = 100;

    /**
     * @var RowManager
     */
    private $manager;

    /**
     * @required
     *
     * @param RowManager $manager
     */
    public function dependencyInjection(RowManager $manager): void
    {
        $this->manager = $manager;
    }

    /**
     * @return int
     *
     * @throws DBALException
     */
    public function getSubscriberPredictCount(): int
    {
        $sql = <<<SQL
            select count(distinct s.id)
            from tca_db.subscriber s
            inner join photo p on s.id = p.subscriber_id
            where s.people is null
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql);

        return (int)$stmt->fetch(FetchMode::COLUMN) ?: 0;
    }

    /**
     * @return Generator
     *
     * @throws DBALException
     */
    public function getSubscriberPhotoList(): Generator
    {
        $sql = <<<SQL
                select
                    s.id subscriber_id,
                    group_concat(p.id) photo_ids,
                    group_concat(p.extension) as extensions
                from subscriber s
                inner join photo p on s.id = p.subscriber_id
                where s.people is null
                group by subscriber_id
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql);

        while ($result = $stmt->fetch()) {
            yield $result['subscriber_id'] => [
                'photo_ids' => $result['photo_ids'],
                'extensions' => $result['extensions'],
            ];
        }

        $stmt->closeCursor();
    }

    /**
     * @param array $subscriberPredictList
     *
     * @throws DBALException
     */
    public function savePredict(array $subscriberPredictList): void
    {
        foreach (array_chunk($subscriberPredictList, self::UPDATE_CHUNK) as $chunkList) {
            $paramsList = [];

            foreach ($chunkList as $subscriberId => $subscriberPredict) {
                $paramsList[] = [
                    'id' => $subscriberId,
                    'people' => (int)$subscriberPredict,
                ];
            }

            $this->manager->updateBulk('subscriber', $paramsList);
        }
    }
}
