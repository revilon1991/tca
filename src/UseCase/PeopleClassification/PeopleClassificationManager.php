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
    private const GROUP_CONCAT_MAX_LENGTH_32_BIT = 4294967295;

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
        $this->manager->getConnection()->exec(sprintf(
            'set session group_concat_max_len=%s',
            self::GROUP_CONCAT_MAX_LENGTH_32_BIT
        ));

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

    /**
     * @param array $peoplePredictPhotoList
     *
     * @throws DBALException
     */
    public function savePhotoPredictPeople(array $peoplePredictPhotoList): void
    {
        foreach (array_chunk($peoplePredictPhotoList, self::UPSERT_CHUNK, true) as $chunkList) {
            $paramsList = [];

            foreach ($chunkList as $photoId => $predict) {
                $paramsList[] = [
                    'id' => $photoId,
                    'people' => $predict,
                ];
            }

            $this->manager->upsertBulk('photo', $paramsList, [
                'people',
            ]);
        }
    }

    /**
     * @param array $peoplePredictSubscriberList
     *
     * @throws DBALException
     */
    public function saveSubscriberPredictPeople(array $peoplePredictSubscriberList): void
    {
        foreach (array_chunk($peoplePredictSubscriberList, self::UPSERT_CHUNK, true) as $chunkList) {
            $paramsList = [];

            foreach ($chunkList as $subscriberId => $predict) {
                $paramsList[] = [
                    'id' => $subscriberId,
                    'people' => $predict,
                ];
            }

            $this->manager->upsertBulk('subscriber', $paramsList, [
                'people',
            ]);
        }
    }
}
