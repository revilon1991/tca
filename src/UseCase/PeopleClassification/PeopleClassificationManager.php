<?php

declare(strict_types=1);

namespace App\UseCase\PeopleClassification;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Generator;

class PeopleClassificationManager
{
    private const UPSERT_CHUNK = 100;

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
            from subscriber s
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
                    gs.subscriber_id,
                    group_concat(distinct gs.group_id) group_ids,
                    group_concat(distinct concat(p.id, '.', p.extension)) photo_names
                from subscriber s
                    inner join photo p on s.id = p.subscriber_id
                    inner join group_subscriber gs on s.id = gs.subscriber_id
                group by gs.subscriber_id
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql);

        while ($result = $stmt->fetch()) {
            yield $result['subscriber_id'] => [
                'group_ids' => $result['group_ids'],
                'photo_names' => $result['photo_names'],
            ];
        }

        $stmt->closeCursor();
    }

    /**
     * @param array $countGroupPeople
     *
     * @throws DBALException
     */
    public function saveReportSubscriberPredictPeople(array $countGroupPeople): void
    {
        $now = date('Y-m-d');

        foreach (array_chunk($countGroupPeople, self::UPSERT_CHUNK, true) as $chunkList) {
            $paramsList = [];

            foreach ($chunkList as $groupId => $countPeople) {
                $paramsList[] = [
                    'date' => $now,
                    'group_id' => $groupId,
                    'count_people' => $countPeople,
                ];
            }

            $this->manager->upsertBulk('report_group', $paramsList, [
                'count_people',
            ]);
        }
    }
}
